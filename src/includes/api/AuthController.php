<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class AuthController extends ApiController {

    function postAction($action)
    {
        switch ($action) {
            case 'login':   $this->response->data = $this->login();         break;
            case 'logout':  $this->response->data = $this->logout();        break;
            case 'session': $this->response->data = $this->createSession(); break;
            default:        $this->response->data = ['total' => 0]; // error 400 ?
        }
    }

    private function login(): ?array
    {
        check_token();
        $t = array('logged' => 0);
        if (!need_auth()) {
            $t['disabled'] = 1;
            return $t;
        }
        $username = $this->req->jsonBody['username'] ?? '';
        $login = Config::getLoginFromUsername($username);
        if (is_null($login)) {
          $t['login'] = '';
          error_log("unknown username '".$username."', IP: ".$_SERVER['REMOTE_ADDR']);
          return $t;
        }
        $password = $this->req->jsonBody['password'] ?? '';
        Config::load($login['login']);
        if ( isPasswordEqualsToHash($password, Config::get('password')) ) {
            updateSessionLogged(true, $login['login']);
            $t['token'] = update_token();
            $t['logged'] = 1;
            $t['login'] = $login['login'];
        } else {
            error_log("wrong password for username '".$username."', IP: ".$_SERVER['REMOTE_ADDR']);
        }
        return $t;
    }

    private function logout(): ?array
    {
        check_token();
        updateSessionLogged(false,'');
        update_token();
        session_regenerate_id(true);
        $t = array('logged' => 0);
        return $t;
    }

    private function createSession(): ?array
    {
        $t = array();
        if (!need_auth()) {
            $t['disabled'] = 1;
            return $t;
        }
        if (access_token() == '') {
            update_token();
        }
        $t['token'] = access_token();
        $t['session'] = session_id();
        return $t;
    }

}
