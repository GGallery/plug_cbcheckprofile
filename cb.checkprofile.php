<?php
/**
 * Community Builder (TM)
 * @version $Id: $
 * @package CommunityBuilder
 * @copyright (C) 2004-2019 www.joomlapolis.com / Lightning MultiCom SA - and its licensors, all rights reserved
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
 */

use CB\Database\Table\UserTable;


/** ensure this file is being included by a parent file */
if (!(defined('_VALID_CB') || defined('_JEXEC') || defined('_VALID_MOS'))) {
    die('Direct Access to this location is not allowed.');
}

global $_PLUGINS;
$_PLUGINS->loadPluginGroup('user');
//$_PLUGINS->registerFunction('onBeforeSaveUserRegistrationRequest', 'checkFields', 'cbcheckprofilePlugin');
$_PLUGINS->registerFunction('onAfterUserLoginSuccess', '_onlogin', 'cbcheckprofilePlugin');

//$_PLUGINS->trigger( 'onAfterLogin', array( &$row, $loggedIn, $firstLogin, &$messagesToUser, &$alertMessages, &$return ) );
//$_PLUGINS->trigger( 'onBeforeSaveUserRegistrationRequest', array( &$msg ) );
class cbcheckprofilePlugin extends cbPluginHandler
{

    public function build_select_query($string_params) {

        if ($string_params == ""
            || empty($string_params))
            return "";

        $_select = "";
        $counter = 0;

        $fields = explode('|*|', $string_params);
        foreach ($fields as $field) {
            $_select .= 'c.' . $field;

            $counter++;
            if ($counter < (count($fields)))
                $_select .= ', ';
        }

        return $_select;

    }

    public function _onlogin($msg)
    {

        $_select = $this->build_select_query($this->params->get('user_must_fields'));
        // se nessun campo è stato impostato non eseguo nessun controllo
        if ($_select == "")
            return true;

        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select($_select)
            ->from('#__users as u')
            ->join('inner', '#__comprofiler AS c ON u.id = c.user_id')
            ->where('u.username ="' . $_REQUEST['username'] . '"');
        $db->setQuery($query);
        $fields = $db->loadAssocList();

        // non succederà ma nel caso controllo l'esistenza di utenti multipli
        if (count($fields) > 1) {
            $msg = "Utenti multipli per lo username " . $_REQUEST['username'];
            $this->_return_error_msg($msg);

            return false;
        }

        // controllo dei campi
        $in_error = false;
        foreach ($fields[0] as $field => $value) {

            if (is_null($value)
                || $value == ""
                || empty($value))
                 {
                     $in_error = true;
                     break;
            }
        }

        // uno dei campi non è valorizzato - vado in errore
        if ($in_error) {
            $_japp = JFactory::getApplication();
            $_japp->redirect(JRoute::_('index.php?option=com_comprofiler&view=userdetails', false), 'Per favore completa il tuo profilo per proseguire', 'warning');
        }

    }

    private function _return_error_msg($msg)
    {
        global $_PLUGINS;
        $_PLUGINS->_setErrorMSG($msg);
    }
}


