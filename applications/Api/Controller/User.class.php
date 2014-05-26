<?php
/**
 * Class Controller_User
 *
 * @author PhpGame
 */

class Controller_User extends Controller_Base
{
    
    /**
     * 用户登陆实例 controller层
     *
     * @return array
     */
    public function action_Login()
    {
        $arr = array();
        $arr = $this->getData;
        $arr[2] = '127.0.0.1';
        $arr[3] = '8282';
        $arr[4] = '192.168.1.168';
        try {
            $result = Library_User::Instance()->userLogin($arr[0], $arr[1], $arr[2], $arr[3], $arr[4]);
            return $this->showResult($result);
        } catch (Exception $ex) {
            return $this->showException();
        }
    }

    /**
     * 用户退出实例 controller层
     *
     * @return array
     */
    public function action_Logout()
    {
        try {
            $result = Library_User::Instance()->userLogout($this->getData);
            return $this->showResult($result);
        } catch (Exception $ex) {
            return $this->showException();
        }
    }

}