<?php
/**
 * This file is part of con4gis,
 * the gis-kit for Contao CMS.
 *
 * @package   	con4gis
 * @version        8
 * @author  	    con4gis contributors (see "authors.txt")
 * @license 	    LGPL-3.0-or-later
 * @copyright 	Küstenschmiede GmbH Software & Design
 * @link              https://www.con4gis.org
 *
 */

namespace con4gis\LdapBundle\Classes;

use con4gis\LdapBundle\Entity\Con4gisLdapFrontendGroups;
use con4gis\LdapBundle\Entity\Con4gisLdapSettings;
use con4gis\LdapBundle\Resources\contao\models\LdapMemberModel;
use Contao\Database;
use Contao\Module;
use con4gis\LdapBundle\Classes\LdapConnection;
use Contao\System;
use League\Uri\Data;
use Psr\Log\LogLevel;
use Contao\CoreBundle\Monolog\ContaoContext;

class CreateLdapAccount
{
    public function onAccountCreation(int $userId, array $userData, Module $module) {
        $db = Database::getInstance();
        $em = System::getContainer()->get('doctrine.orm.default_entity_manager');

        if ($module && $userData && $userId) {
            $ldapSettingsRepo = $em->getRepository(Con4gisLdapSettings::class);
            $ldapSettings = $ldapSettingsRepo->findAll();
            $ldapRegistration = $ldapSettings[0]->getC4gLdapRegistration();

            if ($ldapRegistration == "1") {
                $ldapFrontendGroupsRepo = $em->getRepository(Con4gisLdapFrontendGroups::class);
                $ldapFrontendGroups = $ldapFrontendGroupsRepo->findAll();

                if (empty($ldapSettings)) {
                    \System::getContainer()
                        ->get('monolog.logger.contao')
                        ->log(LogLevel::ERROR, 'Fehler beim Finden der allgemeinen LDAP Einstellungen.', array(
                            'contao' => new ContaoContext(__CLASS__.'::'.__FUNCTION__, TL_CRON
                            )));
                    return false;
                }

                $baseDn = $ldapSettings[0]->getBaseDn();
                $serverType = $ldapSettings[0]->getServerType();
                $groupFilter = $ldapSettings[0]->getGroupFilter();
                $mailField = strtolower($ldapSettings[0]->getEmail());
                $firstnameField = strtolower($ldapSettings[0]->getFirstname());
                $lastnameField = strtolower($ldapSettings[0]->getLastname());

                if ($serverType == "windows_ad") {
                    $userRDNKey = "cn";
                    $userRDNObject = $userData['firstname']." ".$userData['lastname'];
                } else {
                    $userRDNKey = "uid";
                    $userRDNObject = $userData['username'];
                }

                $userPwdPlain = $_POST['password'];
                $userPwdHash = base64_encode(hash("sha512", $userPwdPlain, true));
                $ldapUserPwd = "{SHA512}".$userPwdHash;

                //create array for new ldap entry
                //ToDo: check if username is not null
                $adduserAD["cn"][0] = $userData['username'];
                $adduserAD["uid"][0] = $userData['username'];
                $adduserAD["objectclass"][0] = "inetOrgPerson";
                $adduserAD["objectclass"][1] = "person";
                $adduserAD["objectclass"][2] = "organizationalPerson";
                $adduserAD["objectclass"][3] = "top";
                $adduserAD[$firstnameField][0] = $userData['firstname'] ? $userData['firstname'] : "";
                $adduserAD[$lastnameField][0] = $userData['lastname'] ? $userData['lastname'] : "";
                $adduserAD[$mailField][0] = $userData['email'] ? $userData['email'] : "";
                $adduserAD["userPassword"][0] = $ldapUserPwd;

                //map additional data from frontend group mapping
                $mappingDatas = $ldapFrontendGroups[0]->getFieldMapping();
                if ($mappingDatas) {
                    foreach ($mappingDatas as $mappingData) {
                        $contaoField = $mappingData['contaoField'];
                        if ($contaoField == "") {
                            continue;
                        }
                        $ldapField = $mappingData['ldapField'];
                        $ldapFieldData = $userData[$contaoField];
                        if ($contaoField == 'country') {
                            $ldapFieldData = strtolower($ldapFieldData);
                        }
                        if (!$ldapFieldData || $ldapFieldData == '') {
                            $ldapFieldData = ' ';
                        }
                        $adduserAD[$ldapField][0] = $ldapFieldData;
                    }
                }

                //connect to ldap server
                $ldapConnection = new LdapConnection();
                $ldap = $ldapConnection->ldapConnect();
                $bind = $ldapConnection->ldapBind($ldap);

                //ToDo: check if user is already on the ldap server

                //add user to ldap server
                $ldapRegistrationOu = $ldapSettings[0]->getC4gLdapRegistrationOu();
                $userDn = $userRDNKey."=".$userRDNObject.",".$ldapRegistrationOu;
                ldap_add($ldap, $userDn, $adduserAD);
                $ldapError = ldap_error($ldap);
                if ($ldapError != "Success") {
                    if ($ldapError == "Invalid syntax") {
                        $ldapError = $ldapError.", möglicherweise wurden Felder an den FrontEnd-Gruppen verknüpft, die nicht als Feld im LDAP-Server angelegt werden dürfen.";
                    }
                    \System::getContainer()
                        ->get('monolog.logger.contao')
                        ->log(LogLevel::ERROR, 'Fehler beim Erstellen des LDAP-Eintrags: '.$ldapError, array(
                            'contao' => new ContaoContext(__CLASS__.'::'.__FUNCTION__, TL_CRON
                            )));
                    ldap_unbind($ldap);
                    return false;
                }

                //check for connected ldap groups
                $groups = unserialize($module->reg_groups);
                foreach ($groups as $group) {
                    $group = $db->prepare("SELECT name FROM tl_member_group WHERE con4gisLdapMemberGroup=1 AND id=?")
                        ->execute($group)->fetchAssoc();
                    if ($group) {
                        $registeredGroups[] = $group['name'];
                    }
                }

                //adding registered ldap groups
                if (isset($registeredGroups)) {
                    $ldapGroups = ldap_search($ldap, $baseDn, $groupFilter);
                    if ($ldapGroups) {
                        $ldapGroups = ldap_get_entries($ldap, $ldapGroups);
                        unset($ldapGroups['count']);
                        foreach ($ldapGroups as $ldapGroup) {
                            $groupDn = $ldapGroup['dn'];
                            $rdnArray = explode(",", $groupDn);
                            $rdnFirstObject = str_replace("=", "", strstr($rdnArray[0], "="));
                            if (in_array($rdnFirstObject, $registeredGroups)) {
                                //Add this group to registered member
                                //ToDo: check if user is already in the group
                                $newGroupEntry['member'][0] = $userDn;
                                ldap_mod_add($ldap, $groupDn, $newGroupEntry);
                            }
                        }
                    }
                }

                //close ldap connection
                ldap_unbind($ldap);

                $newMember = LdapMemberModel::findById($userId);
                if ($newMember) {
                    $newMember->con4gisLdapMember = 1;
                    $newMember->password = $ldapConnection->generatePassword();
                    $newMember->save();
                }
            }
        }
    }
}