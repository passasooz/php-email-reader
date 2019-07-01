<?php

namespace Handler;

class Handler {

    private $config;

    public function __construct() {
        $this->config = include('./config/mail.php');
    }

    public function connect() {
        if(!is_array($this->config) || count($this->config) <= 0 || !isset($this->config['host']) || !isset($this->config['port']) || !isset($this->config['protocol']) || !isset($this->config['username']) || !isset($this->config['password'])) return null;

        return imap_open("{".$this->config['host'].":".$this->config['port']."/imap/".$this->config['protocol']."/novalidate-cert}INBOX", $this->config['username'], $this->config['password']);
    }

    public function disconnect($connection = null, $is_expunge = false) {
        if(is_null($connection)) return false;

        if($is_expunge) {
            return imap_close($connection, CL_EXPUNGE);
        }
        
        return imap_close($connection);
    }

    private function _clean_body_msg($message = '') {
        $breaks = ['<br />','<br>','<br/>'];  
        $message = str_ireplace($breaks, "\r\n", $message);  
        return strip_tags($message);
    }

    public function get_all() {
        $result = [
            'status'        => false,
            'message'       => '',
            'emails'        => ''
        ];

        $conn = self::connect();

        if(is_null($conn) || !is_resource($conn)) {
            $result['message'] = 'Impossibile stabilire la connessione';
            return $result;
        }

        $emails = imap_search($conn, 'UNSEEN');
        if(!is_array($emails) || count($emails) <= 0){
            $result['message'] = 'Nessuna email da leggere';
            return $result;
        }
        
        rsort($emails);
        foreach($emails as $email_number) {
            $headers = imap_header($conn, $email_number);

            $overview = imap_fetch_overview($conn, $email_number, 0);

            $email_number;
            $email_data = reset($overview);
            $email_subject = $email_data->subject;
            $email_from = $email_data->from;
            $email_size = $email_data->size;
            $_email_date = strtotime($email_data->date);
            $email_date = date('Y-m-d H:i:s', $_email_date);

            //1.1 TEXT/PLAIN - quoted_printable_decode
            $message = self::_clean_body_msg(imap_fetchbody($conn, $email_number, "1"));
            if(empty($message) || strlen($message) <= 0) {
                $message = self::_clean_body_msg(imap_fetchbody($conn, $email_number, "1.1"));
            }

            if(empty($message) || strlen($message) <= 0) {
                $message = self::_clean_body_msg(imap_fetchbody($conn, $email_number, "1.2"));
            }

            $mailbox = $headers->from[0]->mailbox;
            $domain_host = $headers->from[0]->host;

            $from_address = $mailbox.'@'.$domain_host;

            /*$structure = imap_fetchstructure($conn, $email_number);
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
            }*/




            $result['emails'][$email_number] = [
                'number'            => $email_number,
                'subject'           => $email_subject,
                'from'              => $email_from,
                'address'           => $from_address,
                'size'              => $email_size,
                'date'              => $email_date
            ];
        }
        
        if(!self::disconnect($conn, true)) {
            $result['message'] = 'Errore durante la disconnessione IMAP';
            return $result;
        }

        $result['status'] = true;
        $result['message'] = 'Hai '.count($result['emails']).' e-mail da leggere';
        return $result;
    }
}