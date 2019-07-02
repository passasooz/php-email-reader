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

    private function cleanBodyMsg($message = '') {
        $breaks = ['<br />','<br>','<br/>'];  
        $message = str_ireplace($breaks, "\r\n", $message);  
        return strip_tags($message);
    }

    protected function getEmails($criteria = 'ALL') {
        $result = [
            'status'        => false,
            'message'       => '',
            'emails'        => ''
        ];

        $conn = $this->connect();

        if(is_null($conn) || !is_resource($conn)) {
            $result['message'] = 'The connection could not be verified';
            return $result;
        }

        $emails = imap_search($conn, $criteria);
        if(!is_array($emails) || count($emails) <= 0){
            $result['message'] = 'No e-mails';
            return $result;
        }
        
        rsort($emails);
        foreach($emails as $email_number) {
            $headers = imap_header($conn, $email_number);

            $overview = imap_fetch_overview($conn, $email_number, 0);
            $email_data = reset($overview);
            $email_subject = $email_data->subject;
            $email_from = $email_data->from;
            $email_size = $email_data->size;
            $_email_date = strtotime($email_data->date);
            $email_date = date('Y-m-d H:i:s', $_email_date);
            $email_is_seen = $email_data->seen;

            //1.1 TEXT/PLAIN - quoted_printable_decode
            $message = $this->cleanBodyMsg(imap_fetchbody($conn, $email_number, "1"));
            if(empty($message) || strlen($message) <= 0) {
                $message = $this->cleanBodyMsg(imap_fetchbody($conn, $email_number, "1.1"));
            }

            if(empty($message) || strlen($message) <= 0) {
                $message = $this->cleanBodyMsg(imap_fetchbody($conn, $email_number, "1.2"));
            }

            $mailbox = $headers->from[0]->mailbox;
            $domain_host = $headers->from[0]->host;

            $from_address = $mailbox.'@'.$domain_host;

            $result['emails'][$email_number] = (object)[
                'number'            => $email_number,
                'subject'           => $email_subject,
                'from'              => $email_from,
                'address'           => $from_address,
                'size'              => $email_size,
                'date'              => $email_date,
                'seen'              => $email_is_seen
            ];

            if(!$email_is_seen) {
                imap_clearflag_full($conn, $email_number, "\\Seen");
            }
        }
        
        if(!$this->disconnect($conn, true)) {
            $result['message'] = 'Error on IMAP disconnect';
            return $result;
        }

        $result['status'] = true;
        $result['message'] = count($result['emails']).' e-mails';
        return $result;
    }

    public function all() {
        return $this->getEmails();
    }

    public function unseen() {
        return $this->getEmails('UNSEEN');
    }

    public function seen() {
        return $this->getEmails('SEEN');
    }

    public function deleted() {
        return $this->getEmails('DELETED');
    }
}