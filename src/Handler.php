<?php

namespace Handler;

class Handler {

	private $config;

	public function __construct() {
		$this->config = file_get_contents('./config/mail.php');
	}
	//private $config = file_get_contents('../config/mail.php');

	public function connect($host = null, $port = null, $protocol = null, $username = null, $password = null) {

		if(strlen($host) <= 0 || is_null($host) || !is_numeric($port) || is_null($port) || strlen($protocol) <= 0 || is_null($protocol) || strlen($username) <= 0 || is_null($username) || strlen($password) <= 0 || is_null($password)) return null;

		return imap_open("{".$host.":".$port."/imap/".$protocol."/novalidate-cert}INBOX", $username, $password);
	}

	private function _clean_body_msg($message = '') {
        $breaks = ['<br />','<br>','<br/>'];  
        $message = str_ireplace($breaks, "\r\n", $message);  
        return strip_tags($message); //per sicurezza
    }

    public function get_all() {
    	print_r($this->config);
    	return $config;
		$conn = self::connect($settings['host'], $settings['port'], $settings['protocol'], $settings['username'], $settings['password']);

		if(is_null($conn) || !is_resource($conn)) {
			//ritorna errore connessione imap
		}

		$emails = imap_search($conn, 'UNSEEN');
        if(!is_array($emails) || count($emails) <= 0){
            //nessuna e-mail da leggere
        }
		
		rsort($emails);
		foreach($emails as $email_number) {
			$headers = imap_header($conn, $email_number);

			$overview = imap_fetch_overview($conn, $email_number, 0);

			$email_number;
			$email_data = reset($overview);
			$email_subject = $email_data->subject;
			$email_from = $email_data->from; //non la sto usando ma teniamola qui
			$email_size = $email_data->size; //non la sto usando ma teniamola qui
			$_email_date = strtotime($email_data->date);
			$email_date = date('Y-m-d H:i:s', $_email_date);

            echo '---------'.PHP_EOL;
            echo $azienda_id_str.'Lettura Email da '.$email_from.' con oggetto "'.$email_data->subject.'" del '.$email_date.PHP_EOL;

            //1.1 TEXT/PLAIN - quoted_printable_decode
            $message = self::_clean_body_msg(imap_fetchbody($conn, $email_number, "1"));
            if(empty($message) || strlen($message) <= 0) {
                $message = self::_clean_body_msg(imap_fetchbody($conn, $email_number, "1.1"));
            }

            if(empty($message) || strlen($message) <= 0) {
                $message = self::_clean_body_msg(imap_fetchbody($conn, $email_number, "1.2"));
            }

			$_timestamp_email = strtotime($email_date);

            $mailbox = $headers->from[0]->mailbox;
            $domain_host = $headers->from[0]->host;

			$from_address = $mailbox.'@'.$domain_host;

			$structure = imap_fetchstructure($conn, $email_number);
			$attachments = [];
			if(isset($structure->parts) && count($structure->parts) > 0) {
				for($i = 0; $i < count($structure->parts); $i++) {

					$attachments[$i] = [
						'is_attachment' => false,
						'filename' => '',
						'name' => '',
						'attachment' => ''
					];
					
					if($structure->parts[$i]->ifdparameters) {
						foreach($structure->parts[$i]->dparameters as $object) {
							if(strtolower($object->attribute) == 'filename') {
								$attachments[$i]['is_attachment'] = true;
								$attachments[$i]['filename'] = $object->value;
							}
						}
					}
					
					if($structure->parts[$i]->ifparameters) {
						foreach($structure->parts[$i]->parameters as $object) {
							if(strtolower($object->attribute) == 'name') {
								$attachments[$i]['is_attachment'] = true;
								$attachments[$i]['name'] = $object->value;
							}
						}
					}
					
					if($attachments[$i]['is_attachment']) {
						$attachments[$i]['attachment'] = imap_fetchbody($conn, $email_number, $i+1);
						if($structure->parts[$i]->encoding == 3) { // 3 = BASE64
							$attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
						}elseif($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
							$attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
						}
					}
				}
			}
		}
		
		imap_close($conn, CL_EXPUNGE);

	}
}