<?
require_once SITE_ROOT."/php/lib/Swift.php";
require_once SITE_ROOT."/php/lib/Swift/Connection/SMTP.php";

if(!defined('EMAIL_FROM')){
	define('EMAIL_FROM','registration-noreply@mycypher.com');
}

if(!defined('EMAIL_FROM_NAME')){
	define('EMAIL_FROM_NAME','MyCypher');
}

class email{
	private $toEmail;
	private $fromEmail;
	private $toName;
	private $fromName;
	public function __construct($email,$name = '',$fromemail = '',$fromname = ''){
		$this->toEmail = $email;
		$this->toName = $name;
		$this->fromEmail = (empty($fromemail)?EMAIL_FROM:$fromemail);
		$this->fromName = (empty($fromname)?EMAIL_FROM_NAME:$fromname);
	}

	private function emailIsValid($otheremail = ''){
		if(empty($otheremail)){
			$email = $this->toEmail;
		}else{
			$email = $otheremail;
		}
		return validateEmail($email);
	}

	private function checkDNS($dns){
		$dns = escapeshellarg($dns);
		exec("nslookup $dns",$out);
		foreach($out as $k=> $m){
			if(instr($m,'NXDOMAIN')){
				return false;
			}
		}
		return true;
	}

	public function swiftIt($subject,$msg){
		if(empty($subject) || empty($msg)){
			return false;
		}
		$email = $this->toEmail;
		$ret = false;
		if(self::emailIsValid($this->toEmail)){
			$name = $this->toName;
			$from = $this->fromEmail;
			$fromName = $this->fromName;
			try {
				//Start Swift
				$swift = new Swift(new Swift_Connection_SMTP((defined('SMTP_SERVER')?SMTP_SERVER:'127.0.0.1:25')));
				//Create the message
				$message = new Swift_Message($subject, $msg);
				//customize names
				$to = new Swift_Address($email, $name);
				$from = new Swift_Address($from, $fromName);
				//Now check if Swift actually sends it
				if($swift->send($message, $to, $from)){
					$log = "sent mail to: $to\n";
					$ret = true;
				}
			} catch (Swift_ConnectionException $e) {
				$log = "There was a problem communicating with SMTP: " . $e->getMessage()."\n";
			} catch (Swift_Message_MimeException $e) {
				$log = "There was an unexpected problem building the email:" . $e->getMessage()."\n";
			}
		}
		return $ret;
	}

	public static function send($email,$name,$subject,$message){
		$obj = new self($email,$name);
		return $obj->swiftIt($subject,$message);
	}

	public static function sendBatch(Array $emails,$subject,$message){
		if(empty($subject) || empty($msg) || empty($emails)){
			return false;
		}
		$obj = new self;
		$from = $obj->fromEmail;
		$fromName = $obj->fromName;
		require_once SITE_ROOT."/php/lib/Swift.php";
		require_once SITE_ROOT."/php/lib/Swift/Connection/SMTP.php";
		require_once SITE_ROOT."/php/lib/Swift/RecipientList.php";
		require_once SITE_ROOT."/php/lib/Swift/BatchMailer.php";
		try {
			//Start Swift
			$swift = new Swift(new Swift_Connection_SMTP((defined('SMTP_SERVER')?SMTP_SERVER:'127.0.0.1:25')));
			//Create the message
			$message = new Swift_Message($subject, $msg);
			//customize names
			$recips = new Swift_RecipientList;
			foreach($emails as $email){
				$recips->addTo($email);
			}
			$from = new Swift_Address($from, $fromName);
			//Now check if Swift actually sends it
			if($swift->sendBatch($message, $recips, $from)){
				$log = "sent mail to: ".var_export($emails,true)."\n";
				$ret = true;
			}
		} catch (Swift_ConnectionException $e) {
			$ret = false;
			$log = "There was a problem communicating with SMTP: " . $e->getMessage()."\n";
		} catch (Swift_Message_MimeException $e) {
			$ret = false;
			$log = "There was an unexpected problem building the email:" . $e->getMessage()."\n";
		}
		return $ret;
	}
}

/*
****PERSONALIZING NAMES EXAMPLE
require_once "lib/Swift.php";
require_once "lib/Swift/Connection/SMTP.php";
 
//Start Swift
$swift = new Swift(new Swift_Connection_SMTP("smtp.your-host.tld"));
 
//Create the message
$message = new Swift_Message("My subject", "My body");
 
//Swift_Address can accept an email address and a name, or just an email address
if ($swift->send($message, new Swift_Address("foo@bar.tld", "Foo Bar"), new Swift_Address("me@mydomain.com"))) echo "Sent";
else echo "Failed";

*****HANDLING ERRORS EXAMPLE
try {
  //Start Swift
  $swift = new Swift(new Swift_Connection_SMTP("smtp.your-host.tld"));
 
  //Create the message
  $message = new Swift_Message("My subject", "My body");
 
  //Now check if Swift actually sends it
  $swift->send($message, "foo@bar.tld", "me@mydomain.com");
  echo "Sent";
} catch (Swift_ConnectionException $e) {
  echo "There was a problem communicating with SMTP: " . $e->getMessage();
} catch (Swift_Message_MimeException $e) {
  echo "There was an unexpected problem building the email:" . $e->getMessage();
}

****MULTIPART EXAMPLE
require_once "lib/Swift.php";
require_once "lib/Swift/Connection/SMTP.php";
 
$swift = new Swift(new Swift_Connection_SMTP("smtp.host.tld"));
 
//Create a message
$message = new Swift_Message("My subject");
 
//Add some "parts"
$message->attach(new Swift_Message_Part("Part 1 of message"));
$message->attach(new Swift_Message_Part("Part <strong>2</strong> of message", "text/html"));
 
//And send like usual
if ($swift->send($message, "joe@bloggs.tld", "me@my-address.com")) echo "Sent";
else echo "Failed";
*/
?>