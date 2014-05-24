<?php
/**
 * Class ApiLib_Model_User
 *
 * @author PhpGame
 */

class ApiLib_Model_User extends ApiLib_Model_Base
{

    const USER_TABLE = 'user';
    const USER_LOGIN_TABLE = 'user_login';
    const ROLE_TABLE = 'role';

    /**
     * @return ApiLib_Model_User
     */
    static public function Instance()
    {
        return parent::InstanceInternal(__CLASS__);
    }

    /**
     * 根据用户名查出用户名和密码
     * 
     * @param $username 用户名
     * 
     * @return Array
     */
    public function getUserInfoByUserName($username)
    {
        return $this->dbGamecity->slave()
            ->select('userid, password')
            ->from(self::USER_TABLE)
            ->where(array('username'=>$username, 'state'=>1))
            ->getRow();
    }

    /**
     * 更新用户IP到用户表
     *
     * @param string $username 用户名
     * @param string $loginip  登陆IP
     *
     * @return Boolean
     */
    public function updateUserLoginByUserId($userid, $loginip)
    {
        return $this->dbGamecity->master()
            ->update(self::USER_LOGIN_TABLE, 
                    array('loginmode' => 1,
                           'lastlogip' => $loginip,
                           'lastlogtime' => time()),
                    array('userid'=>$userid));
    }
    
    /**
     * 根据用户ID更新用户登陆信息到用户登陆表
     *
     * @param string $userid   用户ID
     * @param array  $userinfo 用户信息
     *
     * @return Boolean
     */
    public function updateUserLoginAllByUserId($userid, $userinfo = array())
    {
        return $this->dbGamecity->master()
            ->update(self::USER_LOGIN_TABLE, 
                     $userinfo, 
                     array('userid' => $userid));
    }

    /**
     * 根据用户ID获取用户登陆信息
     *
     * @param integer $userid 用户ID
     *
     * @return Boolean
     */
    public function getUserLoginByUserId($userid)
    {
        return $this->dbGamecity->slave()
            ->select('userid')
            ->from(self::USER_LOGIN_TABLE)
            ->where(array('userid'=>$userid))
            ->getOne();
    }

    /**
     * 写入用户登陆信息
     *
     * @param array $userinfo 用户登陆信息
     *
     * @return Boolean || integer
     */
    public function insertUserLogin($userinfo)
    {
        return $this->dbGamecity->master()
            ->insert(self::USER_LOGIN_TABLE, $userinfo);
    }

    /**
     * 删除用户登陆信息
     *
     * @param integer $userid 用户id
     *
     * @return Boolean
     */
    public function deleteUserLogin($userid)
    {
        return $this->dbGamecity->master()
            ->delete(self::USER_LOGIN_TABLE, 
                     array('userid' => $userid));
    }

}