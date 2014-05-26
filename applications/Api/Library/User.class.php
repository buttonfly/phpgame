<?php
/**
 * Class Library_User
 *
 * @author PhpGame
 */

class Library_User extends Library_Base
{
    const INC_SYSTEM_PASSWORD_KEY = 't53h45lo7y';
    
    /**
     * @return Library_User
     */
    static public function Instance()
    {
        return parent::InstanceInternal(__CLASS__);
    }

    /**
     * 用户登陆实例
     * 
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $ip       服务器IP
     * @param string $port     端口
     * @param string $loginip  客户端访问IP
     * 
     * @return array || integer
     */
    public function userLogin($username, $password, $ip, $port, $loginip)
    {
        // 密码加工
        $password = md5($password.self::INC_SYSTEM_PASSWORD_KEY);

        $garr = array();
        //::1 retrieve member site login info 这里注意用户名和密码的过滤特殊字符
        echo $username;
        $userinfo = Library_Model_User::Instance()->getUserInfoByUserName($username);
        if ($userinfo) {
            if ($userinfo['password'] == $password) {
                $userid = $userinfo['userid'];
                $gkey = $this->cache->get('u'.$userid);
                if (empty($gkey)) {
                    $gkey = md5($userid + "_" + time());
                }
                // 缓存登陆数据
                $this->_setUserMemcache($userid, $loginip, $port, $gkey);
                $garr['clientid'] = $gkey;
                $garr['ip']       = $loginip;
                $garr['uid']      = $userid;
                $garr['port']     = $port;
                $garr['lg']       = 1;
                $garr['key']      = $this->enkey($gkey);
                return $garr;
            } else {
                return '10002'; //密码错误
            }
        } else {
            return '10001'; //账号错误
        }
        // 更新用户IP到用户表
        $result = Library_Model_User::Instance()->updateUserLoginByUserId($userid, $loginip);
        if ($result) {
            $key = md5($userid + "_" + time());
            //::3 replace user_logon data 根据用户ID获取用户登陆信息
            $result_user = Library_Model_User::Instance()->getUserLoginByUserId($userid);
            if ($result_user) {
                $userinfo = array('`key`' => $key,
                        'cip' => $loginip,
                        'port' => $port,
                        'sip' => $ip,
                        'loginflag' => '1',
                        'slogintime' => time(),
                        'alogintime' => time()
                );
                // 根据用户ID更新用户登陆信息到用户登陆表
                Library_Model_User::Instance()->updateUserLoginAllByUserId($userid, $userinfo);
            } else {
                $userinfo = array('userid' => $userid,
                        '`key`' => $key,
                        'cip' => $loginip,
                        'port' => $port,
                        'sip' => $ip,
                        'loginflag' => '1',
                        'slogintime' => time(),
                        'alogintime' => time()
                );
                // 写入用户登陆信息
                Library_Model_User::Instance()->insertUserLogin($userinfo);
            }
            // 设置用户登陆缓存
            $this->_setUserMemcache($userid, $loginip, $port, $key);
            $garr['clientid'] = $key;
            $garr['ip']       = $loginip;
            $garr['uid']      = $userid;
            $garr['port']     = $port;
            $garr['lg']       = 1;
            $garr['key']      = $this->enkey($key);
            return $garr;
        } else {
            return '10003';
        }
    }

    /**
     * 设置用户登陆缓存
     *
     * @param string $userid 用户名
     * @param string $ip     服务器IP
     * @param string $port   端口
     * @param string $key    用户登陆标识
     */
    private function _setUserMemcache($userid, $ip, $port, $key)
    {
        /* 存储在缓存的信息
         * 
         * 第一组数据 以键 u.$userid 来存储 用户登陆标识 $key
         * 
         * 第二组数据 以键 $key 来存储 用户信息数组$sarr
         * u userid
         * l 登陆情况
         * i 服务器Ip
         * P 端口
         */
        $sarr = array();
        $sarr['u'] = $userid;
        $sarr['l'] = 1;
        $sarr['i'] = $ip;
        $sarr['p'] = $port;
        // 用memcache来存储第一组数据
        if ($this->cache->set('u'.$userid, $key, 0)) {
            // 用memcache来存储第二组数据
            $this->cache->set($key, $sarr, 0);
        }
    }

    /**
     * 用户登出
     *
     * @param string $key 用户登陆的标识
     *
     * @return Library_User
     */
    public function userLogout($key)
    {
        $userinfo = $this->cache->get($key);
        // 缓存删除用户登陆的第一组数据
        $this->cache->delete($key);
        // 缓存删除用户登陆的第二组数据
        $this->cache->delete('u'.$userinfo['u']);
        // 删除用户登陆表的数据
        Library_Model_User::Instance()->deleteUserLogin($userinfo['u']);
        return true;
    }

}