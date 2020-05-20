<?php

/**
 * con4gis - the gis-kit
 *
 * @version   php 7
 * @package   con4gis
 * @author    con4gis contributors (see "authors.txt")
 * @license   GNU/LGPL http://opensource.org/licenses/lgpl-3.0.html
 * @copyright Küstenschmiede GmbH Software & Design 2011 - 2018
 * @link      https://www.kuestenschmiede.de
 */

use Contao\Message;
use Contao\UserGroupModel;
use con4gis\AuthBundle\Classes\LdapConnection;

/**
 * Table tl_c4g_auth_settings
 */
$GLOBALS['TL_DCA']['tl_c4g_auth_settings'] = array
(

    // Config
    'config' => array
    (
        'dataContainer'               => 'Table',
        'enableVersioning'            => false,
        'notDeletable'                => true,
        'notCopyable'                 => true,
        'onload_callback'			  => array
        (
            array('tl_c4g_auth_settings', 'loadDataset'),
        ),
    ),
    'list' => array
    (
        'sorting' => array
        (
            'mode'                    => 2,
            'fields'                  => array('id'),
            'panelLayout'             => 'filter;sort,search,limit',
            'headerFields'            => array('bindDn', 'baseDn', 'password', 'filter'),
        ),
        'label' => array
        (
            'fields'                  => array('bindDn', 'baseDn', 'password', 'filter'),
            'showColumns'             => true,
        ),
        'global_operations' => array
        (
            'all' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'                => 'act=select',
                'class'               => 'header_edit_all',
                'attributes'          => 'onclick="Backend.getScrollOffset();" accesskey="e"'
            )
        ),
        'operations' => array
        (
            'edit' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_c4g_auth_settings']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'edit.svg',
            )
        )
    ),

    // Select
    'select' => array
    (
        'buttons_callback' => array()
    ),

    // Edit
    'edit' => array
    (
        'buttons_callback' => array()
    ),

    // Palettes
    'palettes' => array
    (
        '__selector__'                => array(''),
        'default'                     => '{ldap}, server, port, encryption, baseDn, bindDn, password, userFilter, email, firstname, lastname'
    ),

    'subpalettes' => array
    (
        ''                                 => ''
    ),

    // Fields
    'fields' => array
    (
        'id' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_c4g_auth_settings']['id'],
            'sorting'                 => true,
            'search'                  => true,
        ),

        'tstamp' => array(
            'default'                 => 0,
        ),

        'bindDn' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_c4g_auth_settings']['bindDn'],
            'sorting'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'default'                 => '',
            'eval'                    => ['mandatory' => true, 'decodeEntities' => true, 'tl_class' => 'long'],
        ),

        'baseDn' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_c4g_auth_settings']['baseDn'],
            'sorting'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'default'                 => '',
            'eval'                    => ['mandatory' => true, 'decodeEntities' => true],
        ),

        'password' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_c4g_auth_settings']['password'],
            'default'                 => '',
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => ['mandatory' => true, 'decodeEntities' => true, 'tl_class' => 'long',],
        ),

        'encryption' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_c4g_auth_settings']['encryption'],
            'exclude'                 => true,
            'filter'                  => false,
            'inputType'               => 'select',
            'options'                 => [
                'plain'               => &$GLOBALS['TL_LANG']['tl_c4g_auth_settings']['plain'],
                'ssl'                 => &$GLOBALS['TL_LANG']['tl_c4g_auth_settings']['ssl'],
            ],
            'default'                 => 'plain',
            'eval'                    => ['submitOnChange' => false],
        ),

        'server' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_c4g_auth_settings']['server'],
            'sorting'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'default'                 => '',
            'eval'                    => array('mandatory' => true, 'decodeEntities' => true, 'tl_class' => 'long'),
        ),

        'port' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_c4g_auth_settings']['port'],
            'sorting'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'default'                 => '',
            'eval'                    => array('mandatory' => true, 'decodeEntities' => true, 'tl_class' => 'long'),
        ),

        'email' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_c4g_auth_settings']['email'],
            'sorting'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'default'                 => '',
            'eval'                    => array('mandatory' => true, 'decodeEntities' => true,),
        ),

        'firstname' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_c4g_auth_settings']['firstname'],
            'sorting'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'default'                 => '',
            'eval'                    => ['decodeEntities' => true, 'tl_class' => 'long'],
        ),

        'lastname' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_c4g_auth_settings']['lastname'],
            'sorting'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'default'                 => '',
            'eval'                    => ['decodeEntities' => true, 'tl_class' => 'long'],
        ),

        'userFilter' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_c4g_auth_settings']['userFilter'],
            'sorting'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'default'                 => '',
            'eval'                    => array('decodeEntities' => true, 'mandatory' => true),
        ),
    ),
);
class tl_c4g_auth_settings extends \Backend
{
    public function loadDataset(Contao\DataContainer $dc)
    {
        $objConfig = Database::getInstance()->prepare("SELECT id FROM tl_c4g_auth_settings")->execute();

        if (\Input::get('key')) return;

        if(!$objConfig->numRows && !\Input::get('act'))
        {
            $this->redirect($this->addToUrl('act=create'));
        }


        if(!\Input::get('id') && !\Input::get('act'))
        {
            $GLOBALS['TL_DCA']['tl_c4g_settings']['config']['notCreatable'] = true;
            $this->redirect($this->addToUrl('act=edit&id='.$objConfig->id));
        }

        \Contao\Message::addInfo($GLOBALS['TL_LANG']['tl_c4g_auth_settings']['infotext']);

        $ldapConnection = new LdapConnection();

        if (!$ldapConnection->ldapBind()) {
            Message::addError($GLOBALS['TL_LANG']['tl_c4g_auth_settings']['bindError']);
        }

    }
}