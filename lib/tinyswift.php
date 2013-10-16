<?php

class TinySwift
{

	public function send($data = array())
	{
		if ( ! is_array($data))
		{
			return false;
		}

		extract($data);

		if ( ! isset($to) or ! $to)
		{
			$to = array(Settings::get('contact_email') => Settings::get('contact_email'));
		}

		//$message_html = ( ! isset($message_html) ) ? nl2br($message) : $message_html;

		if(isset($from) AND isset($from_name))
		{
			$from = array($from => $from_name);
		}
		elseif (isset($from))
		{
			if ( ! is_array($from))
			{
				$from = array($from => $from);
			}
		}
		else
		{
			$from = array(Settings::get('server_email') => Settings::get('server_email'));
		}


		if (is_array($to))
		{
			$new_to = array();
			if (isset($to_name) and $to_name)
			{
				foreach ($to as $t)
				{
					$new_to[$t] = $to_name;
				}

				$to = $new_to;
			}
			else
			{
				// leave $to as is
			}
		}
		elseif (isset($to_name))
		{
			$to = array($to => $to_name);
		}
		else
		{
			$to = array($to => $to);
		}

		$protocol = Settings::get('mail_protocol');

		if ($protocol == 'smtp')
		{
			$host = Settings::get('mail_smtp_host');
			$security = (strpos($host, 'gmail') !== false) ? 'tls' : null;

			$transport = Swift_SmtpTransport::newInstance($host, Settings::get('mail_smtp_port'), $security)
				->setUsername(Settings::get('mail_smtp_user'))
				->setPassword(Settings::get('mail_smtp_pass'));
		}
		elseif ($protocol == 'mail')
		{
			$transport = Swift_MailTransport::newInstance();
		}
		// Sendmail left..
		else
		{
			// You can run the Sendmail Transport in two different modes specified by command line flags:
			// 	"-bs" runs in SMTP mode so theoretically it will act like the SMTP Transport
			// 	"-t" runs in piped mode with no feedback, but theoretically faster, though not advised.
			// If you run sendmail in "-t" mode you will get no feedback as to whether or not sending has succeeded.
			// Use "-bs" unless you have a reason not to.

			$transport = Swift_SendmailTransport::newInstance(Settings::get('mail_sendmail_path', '/usr/sbin/sendmail -bs'));
		}

		( ! isset($reply_to) or ! $reply_to) and $reply_to = $from;

		$mailer = Swift_Mailer::newInstance($transport);

		$message = Swift_Message::newInstance()
		//Give the message a subject
			->setSubject($subject)
		//Set the From address with an associative array
			->setFrom($from)
		//Set the To addresses with an associative array
			->setTo($to)
		//Set reply-to address
			->setReplyTo($reply_to)
		//Give it a body
			->setBody($message_html, 'text/html')
		//And optionally an alternative body
			->addPart($message, 'text/plain');

		if (isset($bcc) and $bcc)
		{
			//is_string($bcc) and $bcc = array($bcc);
			$message = $message->setBcc($bcc);
		}

		if (isset($attach) and $attach)
		{
			(is_string($attach)) and $attach = array($attach);

			foreach ($attach as $i)
			{
				$message->attach(Swift_Attachment::fromPath($i));
			}
		}

		// Send the message
		// returns count of sent messages
		return $mailer->send($message);
	}
}
