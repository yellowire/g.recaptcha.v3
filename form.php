<?php

// Clean up the input values
foreach($_POST as $key => $value) {
    if(ini_get('magic_quotes_gpc'))
        $_POST[$key] = stripslashes($_POST[$key]);

    $_POST[$key] = htmlspecialchars(strip_tags($_POST[$key]));
}

// Assign the input values to variables for easy reference
$name = htmlspecialchars($_POST["name"]);
$email = htmlspecialchars($_POST["email"]);
$message = htmlspecialchars($_POST["message"]);

// Test input values for errors
$errors = array();
if(strlen($name) < 2) {
    if(!$name) {
        $errors[] = "Please enter your name.";
    } else {
        $errors[] = "Name should be at least two characters.";
    }
}
if(!$email) {
    $errors[] = "Please enter your email.";
} else if(!validEmail($email)) {
    $errors[] = "Please enter a valid email.";
}
if(strlen($message) < 10) {
    if(!$message) {
        $errors[] = "Please enter a message.";
    } else {
        $errors[] = "Message should be at least 10 characters.";
    }
}

// check recaptcha assessment
if (isset($_POST['g-recaptcha-response'])) {
    $captcha = $_POST['g-recaptcha-response'];
} else {
    $captcha = false;
}

if (!$captcha) {
    $errors[] = "There was an error validating your submission. Please try again.";
} else {
    $secret   = 'RECAPTCHA SECRET GOES HERE';
    $response = file_get_contents(
        "https://www.google.com/recaptcha/api/siteverify?secret=" . $secret . "&response=" . $captcha . "&remoteip=" . $_SERVER['REMOTE_ADDR']
    );
    // use json_decode to extract json response
    $response = json_decode($response);

    if ($response->success === false) {
        $errors[] = "There was an error validating your submission. Please try again.";
    }
}

// filter access using $response . score
if ($response->success==true && $response->score <= 0.5) {
    $errors[] = "Your site engagement has triggered our bot-senses. Are you a robot?";
}

if($errors) {
    // Output errors and die with a failure message
    header("HTTP/1.1 418 Errors Encountered");
    $errortext = "";
    foreach($errors as $error) {
        $errortext .= "<li>".$error."</li>";
    }
    die("The following errors occured:<ul>". $errortext ."</ul>");
}


// Send the email

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';

$mail = new PHPMailer;
$mail->setFrom('ENTER AUTO ALERT EMAIL ADDRESS','AUTO ALERTS');
$mail->AddAddress('ENTER RECIPIENT EMAIL ADDRESS');
$mail->Subject = 'Contact Form Submission';
$mail->isHTML(true);
$mail->Body = '<p>The following message was sent via the contact form from '.$name.' (<    a href="mailto:'.$email.'">'.$email.'</a>):</p>
<p><em>'.$message.'</em></p>
<br>
<p>User was advised that (s)he will be contacted via the provided email.</p>';
if (!$mail->send()) {
    $mailError = new PHPMailer;
    $mailError->setFrom('ENTER AUTO ALERT EMAIL ADDRESS','AUTO ALERTS');
    $mailError->AddAddress('ENTER RECIPIENT EMAIL ADDRESS');
    $mailError->Subject = 'Contact Form Fail';
    $mailError->Body = 'There was an error sending a contact form submission email from '.$email.' : 
    '.$mail->ErrorInfo.'
    Request URI: '.$_SERVER['REQUEST_URI'].'
    Remote IP: '.$SERVER['REMOTE_ADDR'].'
    User Agent: '.$_SERVER['HTTP_USER_AGENT'];
    $mailError->send();
    header("HTTP/1.1 418 Errors Encountered");
    die("There was a problem on our end in sending your message&mdash;we apologize! Please try again later, or contact the webmaster directly.");
}

// Die with a success message
die("Thanks for reaching out. We'll be in touch soon via the email you provided.");



// A function that checks to see if
// an email is valid
function validEmail($email)
{
   $isValid = true;
   $atIndex = strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex)
   {
      $isValid = false;
   }
   else
   {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64)
      {
         // local part length exceeded
         $isValid = false;
      }
      else if ($domainLen < 1 || $domainLen > 255)
      {
         // domain part length exceeded
         $isValid = false;
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.')
      {
         // local part starts or ends with '.'
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $local))
      {
         // local part has two consecutive dots
         $isValid = false;
      }
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
      {
         // character not valid in domain part
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $domain))
      {
         // domain part has two consecutive dots
         $isValid = false;
      }
      else if(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                 str_replace("\\\\","",$local)))
      {
         // character not valid in local part unless 
         // local part is quoted
         if (!preg_match('/^"(\\\\"|[^"])+"$/',
             str_replace("\\\\","",$local)))
         {
            $isValid = false;
         }
      }
      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
      {
         // domain not found in DNS
         $isValid = false;
      }
   }
   return $isValid;
}

?>
