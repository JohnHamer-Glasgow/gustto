<?php
require_once('corelib/form_lib2.php');

class add_comment extends nbform
{
	var $form_magic_id = '78cc37bffac03af8f3d6b5b321c75ffr'; // ???
	var $id; //hidden
	var $user_id;
	var $teachingpractice_id;
	var $time;
	var $comment;	

	function __construct($readform=true)
	{
		parent::__construct();
		$this->validateMessages = array();
		if($readform)
		{
			$this->readAndValidate();
		}
	}

	function setData($data)
	{
		$this->id = $data->id;
		$this->user_id = $data->user_id;
		$this->teachingpractice_id = $data->teachingpractice_id;
		$this->time = $data->time;
		$this->comment = $data->comment;
	}		

	function getData(&$data)
	{
		$data->id = $this->id;
		$data->user_id = $this->user_id;
		$data->teachingpractice_id = $this->teachingpractice_id;
		$data->time = $this->time;
		$data->comment = $this->comment;
		return $data;
	}

	function readAndValidate()
	{
		$isCanceled=false;
		if((isset($_REQUEST['addbundle_form_code']))&&($_REQUEST['addbundle_form_code'] == $this->form_magic_id))
		{		
			$this->comment = stripslashes($_REQUEST['comment']);
			$this->id = $_REQUEST['id'];
			$this->user_id = stripslashes($_REQUEST['user_id']);
			$this->teachingpractice_id = stripslashes($_REQUEST['teachingpractice_id']);

			if("Cancel" == $_REQUEST['submit'])			
				$isCanceled = true;
			$isValid = $this->validate();
			if($isCanceled)
				$this->formStatus = FORM_CANCELED;
			elseif($isValid)
				$this->formStatus = FORM_SUBMITTED_VALID;
			else
				$this->formStatus = FORM_SUBMITTED_INVALID;
		}
		else
			$this->formStatus = FORM_NOTSUBMITTED;
	}

	function validate()
	{
		$this->validateMessages = array();
		if(strlen($this->comment)>100)
		{
		    $this->comment = substr($this->comment,0,100);
		    $this->validateMessages['comment'] = "This field was too long and has been truncated.";
		}
		if(strlen($this->comment)<5)
		{
		    $this->validateMessages['comment'] = "Comment needs to be at least 5 characters long..";
		}

		if(sizeof($this->validateMessages)==0)
			return true;
		else
			return false;
	}

	function getHtml()
	{
		$out = '';
		$out .= $this->formStart();
		$out .= $this->hiddenInput('addbundle_form_code', $this->form_magic_id);
		$out .= $this->textareaInput('', 'comment', $this->comment, $this->validateMessages, 70 , 8);
		$out .= $this->hiddenInput('id', $this->id);
		$out .= $this->hiddenInput('user_id', $this->user_id);
		$out .= $this->hiddenInput('time', $this->time);
		$out .= $this->hiddenInput('teachingpractice_id', $this->teachingpractice_id);
		$out .= $this->submitInput('submit', 'Add', "Cancel");
		$out .= $this->formEnd(false);
		return $out;
	}
	
		
	function post_it()
	{
	    $http = new Http();
	    $http->useCurl(false);
	    $formdata=array('thanks_url'=>'none', 'mymode'=>'webform1.0', 'datafile'=>'addbundle_form', 'coderef'=>'nsb2x');
	    $formdata['id'] = $this->id;
	    $formdata['user_id'] = $this->user_id;
	    $formdata['teachingpractice_id'] = $this->teachingpractice_id;
	    $formdata['time'] = $this->time;
	    $formdata['comment'] = $this->comment;

	    $http->execute('http://culrain.cent.gla.ac.uk/cgi-bin/qh/qhc','','POST',$formdata);
	    return ($http->error) ? $http->error : $http->result;
	}

}

