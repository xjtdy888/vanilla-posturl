<?php if (!defined('APPLICATION')) exit();



$PluginInfo['PostUrl'] = array(
	'Author' => 'xjtdy888',
	'AuthorUrl' => 'http://github.com/xjtdy888',
    'Name' => 'PostUrl',
    'Description' => '添加版权展示信息',
    'Version' => '1.0',
    'MobileFriendly' => TRUE,
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'RequiredTheme' => FALSE,
    'RequiredPlugins' => FALSE,
    'SettingsUrl' => '/settings/PostUrl',
   'SettingsPermission' => 'Garden.Settings.Manage'
);

class PostUrlPlugin extends Gdn_Plugin {
    /**
     * Setup is called when the plugin is enabled.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Apply database structure updates
     */
    public function structure() {
        $PM = new PermissionModel();

        $PM->define(array(
            'Plugins.PostUrl.Attach' => 'Plugins.PostUrl.Attach'
        ));

        $Structure = Gdn::structure();
        $Structure
            ->table('PostUrl')
            ->column('DiscussionID', 'int(11)', false, 'unique')
            ->column('PostUrlValue', 'int(11)')
            ->column('DateInserted', 'datetime')
            ->set(false, false);

    }
    public function discussionController_afterDiscussionBody_handler($Sender, $args) {
    	if (c('Plugins.PostUrl.Disable', false)) {
    	    return;
    	}
        $DiscussionID = property_exists($Sender, 'DiscussionID') ? $Sender->DiscussionID : 0;

        if (!$DiscussionID) {
            return;
        }
        
        $ConfigKey = $this->getConfigKey($DiscussionID);       
        if ($ConfigKey === false) {
            return ;
        } 

    	$placeHolders = array(
        	'%site_url%' => c('Garden.Domain'),
        	'%site_name%' => c('Garden.Title'),
        	'%post_url%' => getValueR("Discussion.Url", $args),
        	'%post_title%' => getValueR("Discussion.Name", $args),
    	);
    	$display = c($ConfigKey);

    	$appendContent = str_replace(array_keys($placeHolders), array_values($placeHolders), $display);
    	echo $appendContent;
    }
    public function getDisplayValueID($DiscussionID) {
        $id = $this->getDiscussionValueID($DiscussionID) || intval(c('Plugins.PostUrl.Default'));
        return $id;
    }
    public function getDiscussionValueID($DiscussionID) {
        $SQL = Gdn::sql();
        $row = $SQL->select("*")
            ->from("PostUrl")
            ->where("DiscussionID", $DiscussionID)
            ->get()->firstRow();
        $ValueID = property_exists($row, "PostUrlValue") ? $row->PostUrlValue : 0;
        return $ValueID;
    }

    public function getConfigKey($DiscussionID) {
        $ValueID = $this->getDisplayValueID($DiscussionID);
        if (!$ValueID){
            return false;
        }
        $ConfigKey = sprintf("Plugins.PostUrl.Display%d", $ValueID);
        return $ConfigKey;
    }
    public function settingsController_PostUrl_create($Sender, $args) {
        $Sender->permission('Garden.Settings.Manage');
        $Cf = new ConfigurationModule($Sender);

        if (c('Plugins.PostUrl.Display1') == "") {
            Gdn::config()->set('Plugins.PostUrl.Display1', "from: %post_url%", false, false);
        }
        $Description = '<p>可接受下列占位符号，该符号和wordpress兼容: <br />%site_url% - the URI of your site<br />%site_name% - the name of your site<br />%post_url% - the URI of the post where the text is displayed<br />%post_title% - the title of the post where the text is displayed</p>';

        $Cf->initialize(array(
            'Plugins.PostUrl.ItemName1' => array('LabelCode' => '版权名称一', 'Control' => 'TextBox', 'Description' => "该字段用于发布话题时选择方便记忆"),
            'Plugins.PostUrl.Display1' => array('LabelCode' => '版权信息一', 'Control' => 'TextBox', 'Description' => $Description, 'Items' => array(), 'Options'=>array("MultiLine"=>true, "rows"=>"20", "cols" => 30)),

            'Plugins.PostUrl.ItemName2' => array('LabelCode' => '<hr /><br />版权名称二', 'Control' => 'TextBox', 'Description' => "该字段用于发布话题时选择方便记忆"),
            'Plugins.PostUrl.Display2' => array('LabelCode' => '版权信息二', 'Control' => 'TextBox', 'Options'=>array("MultiLine"=>true, "rows"=>"20", "cols" => 30)),

            'Plugins.PostUrl.ItemName3' => array('LabelCode' => '<hr /><br />版权名称三', 'Control' => 'TextBox', 'Description' => "该字段用于发布话题时选择方便记忆"),
            'Plugins.PostUrl.Display3' => array('LabelCode' => '版权信息三', 'Control' => 'TextBox', 'Options'=>array("MultiLine"=>true, "rows"=>"20", "cols" => 30)),

            'Plugins.PostUrl.Default' => array('LabelCode' => '默认版权信息', 'Control' => 'DropDown', 'Description' => "没有选择或者历史文章将会使用该设定", 'Items'=>array(0=>'', 1=>'版权一',2=>'版权二', 3=>'版权三'), 'Options'=>array('Value'=>c('Plugins.PostUrl.Default')))

        ));

        $c = Gdn::controller();
        $c->addJsFile('settings.js', 'plugins/CDNManager');

        $Sender->addSideMenu();
        $Sender->setData('Title', t('Add Post Url'));
        $Cf->renderAll();
    }

     public function postController_afterDiscussionFormOptions_handler($Sender) {
        if (c('Plugins.PostUrl.Disable', false)) {
            return;
        }
        //Gdn::session()->checkPermission('Plugins.PostUrl.Attach')
        if (in_array($Sender->RequestMethod, array('discussion', 'editdiscussion', 'question'))) {
            $DiscussionID = property_exists($Sender, 'DiscussionID') ? $Sender->DiscussionID : 0;
            $ValueID = $this->getDiscussionValueID($DiscussionID);
            $dropdownOptions = array();
            if ($ValueID) {
                $dropdownOptions["Value"] = $ValueID;
            }
            echo '<div class="PostUrl P">';
            
            $co = array('0' => '');

            if (c("Plugins.PostUrl.ItemName1", "") !== "") {
                $co[1] = c("Plugins.PostUrl.ItemName1");
            }
            if (c("Plugins.PostUrl.ItemName2", "") !== "") {
                $co[2] = c("Plugins.PostUrl.ItemName2");
            }
            if (c("Plugins.PostUrl.ItemName3", "") !== "") {
                $co[3] = c("Plugins.PostUrl.ItemName3");
            }
     
            echo $Sender->Form->label('版权信息选择', 'PostUrlValue');
            echo $Sender->Form->Dropdown("PostUrlValue", $co, $dropdownOptions);
            echo '</div>';
        }
     }

     public function discussionModel_afterSaveDiscussion_handler($Sender) {
        if (c('Plugins.PostUrl.Disable', false)) {
            return;
        }
        $FormPostValues = val('FormPostValues', $Sender->EventArguments, array());
        $PostUrlValue = val('PostUrlValue', $FormPostValues, 0);

        $PostUrlValue = intval($PostUrlValue);
        
        if ($PostUrlValue == 0 || $PostUrlValue > 3) {
            return ;
        }

        $DiscussionID = val('DiscussionID', $Sender->EventArguments, 0);

        $SQL = Gdn::sql();
        $row = $SQL->select("*")
            ->from("PostUrl")
            ->where("DiscussionID", $DiscussionID)
            ->get()->firstRow(); 
        if (!$row) {
            $SQL->insert("PostUrl", array(
                            'DiscussionID' => $DiscussionID,
                            'PostUrlValue' => $PostUrlValue,
                            'DateInserted' => date('Y-m-d H:i:s'))
            );
        }else{
            $row->PostUrlValue = $PostUrlValue;
            $SQL->update("PostUrl", $row)->put();
        }

    }
    /**
     *
     *
     * @param $Sender
     * @throws Exception
     */
    public function discussionModel_deleteDiscussion_handler($Sender) {
        if (c('Plugins.PostUrl.Disable', false)) {
            return;
        }
        // Get discussionID that is being deleted
        $DiscussionID = $Sender->EventArguments['DiscussionID'];
        $SQL = Gdn::sql();
        $SQL->delete("PostUrl", array("DiscussionID", $DiscussionID));
    }
}
