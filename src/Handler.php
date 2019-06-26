<?php
namespace Handler;

class Handler {

	public function connect($host = null, $port = null, $protocol = null, $username = null, $password = null) {

		if(strlen($host) <= 0 || is_null($host) || !is_numeric($port) || is_null($port) || strlen($protocol) <= 0 || is_null($protocol) || strlen($username) <= 0 || is_null($username) || strlen($password) <= 0 || is_null($password)) return null;

		return imap_open("{".$host.":".$port."/imap/".$protocol."/novalidate-cert}INBOX", $username, $password);
	}

	private function _clean_body_msg($message = '') {
        $breaks = ['<br />','<br>','<br/>'];  
        $message = str_ireplace($breaks, "\r\n", $message);  
        return strip_tags($message); //per sicurezza
    }

    public function read() {

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

				$is_reply = false;

				$__search = '#TICK-';
				if(preg_match("/{$__search}/i", $email_subject)) {
				    $is_reply = true;
				}
				
				$azienda_item = $this->aziende_model->get_item($azienda_id);

				//se l'utente non è collegato con la azienda
		        if(!$this->utenti_model->check_if_email_exists($from_address, $azienda_id)){

		            //verificare se esiste un utente con quella e-mail, se esiste ritorna errore
		            $check_user_exists = $this->utenti_model->get_user_by_email($from_address);
		            if(is_array($check_user_exists) && count($check_user_exists) > 0) {
		            	echo $azienda_id_str.' Non è possibile inserire il ticket perchè questo utente esiste su altra azienda.'.PHP_EOL;
		            	imap_clearflag_full($conn, $email_number, "\\Seen");
		            	continue;
		            }

		            //crea l'utente
		            $clean_password = generatePassword(12, 8);

		            $crypted_password = $this->freakauth_light->_encode($clean_password);

		            $crypted_password_clean = $this->encrypt->encode($clean_password, md5(ENCRIPTION_KEY . $from_address));

		            $userdata = [
		                'user_name'                 => $from_address,
		                'email'                     => $from_address,
		                'password'                  => $crypted_password,
		                'password_clean'            => $crypted_password_clean,
                        'role'                      => 'customer',
		                //'original_role'             => 'customer',
		                'superclient'               => 0,
		                'forgotten_password_code'   => mencrypt($crypted_password, ENCRIPTION_KEY),
		                'disabled'                  => 1
		            ];

		            $insert_utente_id = $this->utenti_model->insert($userdata);
		            if(!$insert_utente_id) {
		                echo $azienda_id_str.'Errore inserimento utente'.PHP_EOL;
		                imap_clearflag_full($conn, $email_number, "\\Seen");
		                continue;
		            }

		            //se trovo il punto separo $headers->from[0]->mailbox e metto quelli altrimenti metto $headers->from[0]->mailbox come nome e cognome domino
		            $profiledata_fname = $mailbox;
		            $profiledata_lname = $domain_host;
		            if (strpos($mailbox, '.') !== false) {
					    $profiledata_flname = explode('.', $mailbox);
                        if(is_array($profiledata_flname) 
                            && count($profiledata_flname) > 0){
                            $profiledata_fname = $profiledata_flname[0];
                            $profiledata_lname = $profiledata_flname[1];
                        } 
					}

		            $profiledata = [
		                'id'            => $insert_utente_id,
		                'fname'         => $profiledata_fname,
		                'lname'         => $profiledata_lname,
		                'tel'           => '',
		                'language'      => 'IT'
		            ];
		            //inserimento profilo utente
		            if(!$this->utenti_model->insert_profile($profiledata)){
		                echo $azienda_id_str.'Errore inserimento profilo utente'.PHP_EOL;
		                imap_clearflag_full($conn, $email_number, "\\Seen");
		                continue;
		            }

		            //inserimento relazione utente client ad azienda
		            $insert_relation = $this->utenti_aziende_model->insert([
		                'utenti_azienda_azienda_id' => $azienda_id,
                        //'utenti_azienda_role'       => 'customer',
		                'utenti_azienda_utente_id'  => $insert_utente_id,
		                'utenti_azienda_uid'        => $insert_utente_id
		            ]);
		            if(!$insert_relation) {
		                echo 'Errore inserimento relazione utente - azienda'.PHP_EOL;
		                imap_clearflag_full($conn, $email_number, "\\Seen");
		                continue;
		            }

		            //invio email di attivazione account
		            $vista_email = $this->load->view($this->config->item('FAL_template_dir').TEMPLATE.'/container_email', [
		                'fname'             => ucwords($profiledata['fname']),
		                'lname'             => ucwords($profiledata['lname']),
		                'user_name'         => $userdata['user_name'],
		                'azienda'           => $this->aziende_model->get_item($azienda_id),
		                'activation_code'   => $userdata['forgotten_password_code'],
		                'page'              => $this->config->item(
		                'FAL_template_dir').TEMPLATE.'/utenti/emails/attivazione_nuovo_utente'
		            ], true);

		            $send_activation_mail = $this->myemail_lib->send_mail(FROM_EMAIL, FROM_NAME, $userdata['email'], '['.$tb_conf['ticket_config_imap_username'].'] Attivazione account utente - Azione richiesta', $vista_email);
		            if(!$send_activation_mail) {
		                echo $azienda_id_str.'Errore invio e-mail attivazione utente: '.$userdata['user_name'].PHP_EOL;
                        //non bloccherei se non invia l'email di attivazione
		                /*imap_clearflag_full($conn, $email_number, "\\Seen");
		                continue;*/
		            }
		        }

				$utente_item = $this->utenti_model->get_user_by_email($from_address);
		        if(!is_array($utente_item) || count($utente_item) <= 0) {
		            echo $azienda_id_str.'Utente non trovato tramite email: '.$userdata['user_name'].PHP_EOL;
		            imap_clearflag_full($conn, $email_number, "\\Seen");
		            continue;
		        }

		        if($utente_item['role'] != 'customer') {
		            echo $azienda_id_str.'"'.$email_subject.'" - Impossibile creare ticket poichè utente '.$userdata['user_name'].' non è client ticket'.PHP_EOL;
		            imap_clearflag_full($conn, $email_number, "\\Seen");
		            continue;
		        }

		        if(!$is_reply) { //creo il ticket

		        	$__data = [
		        		'imap_username'		=> $tb_conf['ticket_config_imap_username'],
		        		'email_subject' 	=> $email_subject,
		        		'email_date'		=> $email_date,
		        		'message'			=> $message,
		        		'azienda_id'		=> $azienda_id,
		        		'utente_item'		=> $utente_item,
		        		'azienda_item'		=> $azienda_item
		        	];

		        	$result_ins_tick = self::_insert_ticket($conn, $__data, $attachments);
		        	if($result_ins_tick['status'] === false) {
		        		echo $azienda_id_str.$result_ins_tick['message'].PHP_EOL;
		        		imap_clearflag_full($conn, $email_number, "\\Seen");
		        		continue;
		        	}
		        	echo $azienda_id_str.'"'.$email_subject.'" - '.$result_ins_tick['message'].PHP_EOL;

			        
		    	} else { //creo commento ticket
		    		$remove_tick_obj = explode('#TICK-', $email_subject);

                    if(!is_array($remove_tick_obj) || count($remove_tick_obj) < 2)
                        continue;

		    		$ticket_counter = explode('-', $remove_tick_obj[1]);

                    if(!is_array($ticket_counter) || count($ticket_counter) < 2)
                        continue;

		    		$ticket_counter = reset($ticket_counter);

                    if(!is_numeric($ticket_counter) || $ticket_counter <= 0)
                        continue;

                    $filters = 'ticket_azienda_id = '.$azienda_id.' AND ticket_contatore = '.$ticket_counter.' AND ticket_deleted = 0';

                    //recupero ticket già creato
		    		//$item = $this->ticket_model->get_item(null, $filters);
                    $item = self::_get_ticket_item($filters);

                    if(!is_array($item) || count($item) <= 0)
                        continue;

			        $data = [
			            'ticket_nota_descrizione'	=> $message,
			            'ticket_nota_ticket_id'		=> $item['ticket_id'],
			            'ticket_nota_uid'			=> $utente_item['id'],
			            'ticket_nota_azienda_id'	=> $azienda_id,
			            'ticket_nota_private'		=> 0
			        ];

			        $insert_nota = $this->ticket_model->insert_nota($data);
			        if($insert_nota > 0){

			        	$__data = [
			        		'attachments' 	=> $attachments,
			        		'email_date'	=> $email_date,
			        		'item'			=> $item,
			        		'utente_item'	=> $utente_item,
			        		'azienda_id'	=> $azienda_id,
			        		'azienda_item'	=> $azienda_item,
			        		'data'			=> $data,
			        		'tb_conf'		=> $tb_conf
			        	];

			        	$result_after_ins_nota = self::_after_insert_nota($conn, $__data);

			        	if($result_after_ins_nota['status'] === false) {
			        		echo $azienda_id_str.'"'.$email_subject.'" - '.$result_after_ins_nota['message'].PHP_EOL;
			        		imap_clearflag_full($conn, $email_number, "\\Seen");
			        		continue;
			        	}
			        	echo $azienda_id_str.'"'.$email_subject.'" - '.$result_after_ins_nota['message'].PHP_EOL;

			            
			        }else{
			        	echo $azienda_id_str.'"'.$email_subject.'" - Errore inserimento commento ticket'.PHP_EOL;
			        	imap_clearflag_full($conn, $email_number, "\\Seen");
			        	continue;
			        }
		    	}
		}
		
		imap_close($conn, CL_EXPUNGE);

        sleep(1);
	}
}