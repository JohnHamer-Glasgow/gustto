<?php
require_once('corelib/form_lib2.php');

class addbundle_form extends nbform
{
	var $form_magic_id = '78cc37bffac03af8f3d6b5b321c75ffa';
	var $title; //string
	var $id; //hidden
	var $problemstatement; //memo
	var $thisbundle; //memo
	var $wayitworks; //memo
	var $worksbetter; //memo
	var $doesntwork; //memo
	var $doesntworkunless; //memo
	var $workedif; //memo
	var $variations; //memo
	var $solutionstatement; //memo
	var $validateMessages;

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
		$this->title = $data->title;
		$this->id = $data->id;
		$this->problemstatement = $data->problemstatement;
		$this->thisbundle = $data->thisbundle;
		$this->wayitworks = $data->wayitworks;
		$this->worksbetter = $data->worksbetter;
		$this->doesntwork = $data->doesntwork;
		$this->doesntworkunless = $data->doesntworkunless;
		$this->workedif = $data->workedif;
		$this->variations = $data->variations;
		$this->solutionstatement = $data->solutionstatement;
	}

	function getData(&$data)
	{
		$data->title = $this->title;
		$data->id = $this->id;
		$data->problemstatement = $this->problemstatement;
		$data->thisbundle = $this->thisbundle;
		$data->wayitworks = $this->wayitworks;
		$data->worksbetter = $this->worksbetter;
		$data->doesntwork = $this->doesntwork;
		$data->doesntworkunless = $this->doesntworkunless;
		$data->workedif = $this->workedif;
		$data->variations = $this->variations;
		$data->solutionstatement = $this->solutionstatement;
		return $data;
	}

	function readAndValidate()
	{
		$isCanceled=false;
		if((isset($_REQUEST['addbundle_form_code']))&&($_REQUEST['addbundle_form_code'] == $this->form_magic_id))
		{
			$this->title = stripslashes($_REQUEST['title']);
			$this->id = $_REQUEST['id'];
			$this->problemstatement = stripslashes($_REQUEST['problemstatement']);
			$this->thisbundle = stripslashes($_REQUEST['thisbundle']);
			$this->wayitworks = stripslashes($_REQUEST['wayitworks']);
			$this->worksbetter = stripslashes($_REQUEST['worksbetter']);
			$this->doesntwork = stripslashes($_REQUEST['doesntwork']);
			$this->doesntworkunless = stripslashes($_REQUEST['doesntworkunless']);
			$this->workedif = stripslashes($_REQUEST['workedif']);
			$this->variations = stripslashes($_REQUEST['variations']);
			$this->solutionstatement = stripslashes($_REQUEST['solutionstatement']);
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
		if(strlen($this->title)>80)
		{
		    $this->title = substr($this->title,0,80);
		    $this->validateMessages['title'] = "This field was too long and has been truncated.";
		}
		if(strlen($this->title)<5)
		{
		    $this->validateMessages['title'] = "Title needs to be at least 5 characters long..";
		}
		// Put custom code to validate $this->title here. Error message in $this->validateMessages['title']
		// Put custom code to validate $this->id here (to stop hackers using this as a way in.)
		// Put custom code to validate $this->problemstatement here. Put error message in $this->validateMessages['problemstatement']
		// Put custom code to validate $this->thisbundle here. Put error message in $this->validateMessages['thisbundle']
		// Put custom code to validate $this->wayitworks here. Put error message in $this->validateMessages['wayitworks']
		// Put custom code to validate $this->worksbetter here. Put error message in $this->validateMessages['worksbetter']
		// Put custom code to validate $this->doesntwork here. Put error message in $this->validateMessages['doesntwork']
		// Put custom code to validate $this->doesntworkunless here. Put error message in $this->validateMessages['doesntworkunless']
		// Put custom code to validate $this->workedif here. Put error message in $this->validateMessages['workedif']
		// Put custom code to validate $this->variations here. Put error message in $this->validateMessages['variations']
		// Put custom code to validate $this->solutionstatement here. Put error message in $this->validateMessages['solutionstatement']
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
		$out .= $this->textInput('Teaching Tip Name', 'title', $this->title, $this->validateMessages, 80);
		$out .= $this->hiddenInput('id', $this->id);
		$out .= $this->textareaInput('Rationale statement: (What teaching problem does this address?)', 'problemstatement', $this->problemstatement, $this->validateMessages, 70 , 8);
		$out .= $this->textareaInput('Description - This Teaching Tip ...', 'thisbundle', $this->thisbundle, $this->validateMessages, 70 , 8);
		$out .= $this->textareaInput('What I did', 'wayitworks', $this->wayitworks, $this->validateMessages, 70 , 8);
		$out .= $this->textareaInput('This only works if ...', 'workedif', $this->workedif, $this->validateMessages, 70 , 8);
		$out .= $this->textareaInput('This works better if ...', 'worksbetter', $this->worksbetter, $this->validateMessages, 70 , 8);
		$out .= $this->textareaInput('This doesn\'t work if ...', 'doesntwork', $this->doesntwork, $this->validateMessages, 70 , 8);
		$out .= $this->textareaInput('This doesn\'t work unless ...', 'doesntworkunless', $this->doesntworkunless, $this->validateMessages, 70 , 8);
		$out .= $this->textareaInput('Variations:', 'variations', $this->variations, $this->validateMessages, 70 , 8);
		$out .= $this->textareaInput('So, ... (Solution Statement)', 'solutionstatement', $this->solutionstatement, $this->validateMessages, 70 , 8);
		$out .= $this->submitInput('submit', 'Add', "Cancel");
		$out .= $this->formEnd(false);
		return $out;
	}

	function post_it()
	{
	    $http = new Http();
	    $http->useCurl(false);
	    $formdata=array('thanks_url'=>'none', 'mymode'=>'webform1.0', 'datafile'=>'addbundle_form', 'coderef'=>'nsb2x');
	    $formdata['title'] = $this->title;
	    $formdata['id'] = $this->id;
	    $formdata['problemstatement'] = $this->problemstatement;
	    $formdata['thisbundle'] = $this->thisbundle;
	    $formdata['wayitworks'] = $this->wayitworks;
	    $formdata['worksbetter'] = $this->worksbetter;
	    $formdata['doesntwork'] = $this->doesntwork;
	    $formdata['doesntworkunless'] = $this->doesntworkunless;
	    $formdata['workedif'] = $this->workedif;
	    $formdata['variations'] = $this->variations;
	    $formdata['solutionstatement'] = $this->solutionstatement;

	    $http->execute('http://culrain.cent.gla.ac.uk/cgi-bin/qh/qhc','','POST',$formdata);
	    return ($http->error) ? $http->error : $http->result;
	}

}

