<?php
/**
 * 
 * 命令字相关
* @author walkor <worker-man@qq.com>
* 
 */
require_once GAME_ROOT_DIR . '/Protocols/Buffer.php';
require_once GAME_ROOT_DIR . '/Event.php';

/**
 * 
 * 主命令字对应某个类,子命令字对应某个方法
 * 需要在下面做映射
 * 例如添加一个类Map，类里面有个getPosition方法
 * ①增加主命令字 const CMD_MAP = 2;
 * ②增加子命令字 const SCMD_GET_POSITION= 10;
 * ③$cmdMap增加主命令字到类的映射,self::CMD_MAP => 'Map';
 * ④$scmdMap增加子命令字到方法的映射,self::SCMD_GET_POSITION => 'getPosition'
 * Client调用这个Map的getPosition方法时就可以类似这样调用
 * $buf = new GameBuffer();
 * $buf->header['cmd'] = GameBuffer::CMD_MAP;
 * $buf->header['sub_cmd'] = GameBuffer::SCMD_GET_POSITION;
 * $buf->body = '业务自定义格式的字符串，可以留空';
 * clientSendToServer($buf->getBuffer());//客户端调用类似clientSendToServer的方法把数据发给服务端
 * @author liangl
 */
class GameBuffer extends Buffer
{
    /***********************以下是主命令字**********************/
    // 用户，对应User类
    const CMD_USER = 1;
    // 用户动作，对应Action类
    const CMD_ACTION = 2;
    // =======预留主命令字========
    // 系统命令
    const CMD_SYSTEM = 128;
    // Gateway
    const CMD_GATEWAY = 129;
    
    /**********************以下是子命令字************************/
    // ===CMD_GATEWAY的子命令子===
    // 连接事件 
    const SCMD_ON_CONNECT = 1;
    // 关闭事件
    const SCMD_ON_CLOSE = 2;
    // 给用户发送数据包
    const SCMD_SEND_DATA = 3;
    // 根据uid踢人
    const SCMD_KICK_UID = 4;
    // 根据地址和socket编号踢人
    const SCMD_KICK_ADDRESS = 5;
    // 广播内容
    const SCMD_BROADCAST = 6;
    // 通知连接成功
    const SCMD_CONNECT_SUCCESS = 7;
    // 用户获取数据包
    const SCMD_GET_DATA = 8;
    // ===用户CMD_USER的子命令字===
    // 发言，对应User::say
    const SCMD_SAY = 9;
    // 广播内容,对应User::broadcast
    //const SCMD_BROADCAST = 6;
    
 
    public static $cmdMap = array(
            self::CMD_USER  => 'User',// 对应User类
            self::CMD_GATEWAY => 'GateWay',
            self::CMD_SYSTEM => 'System',
     );
    
    public static $scmdMap = array(
            self::SCMD_BROADCAST     => 'broadcast',//对应broadcast方法
            self::SCMD_ON_CONNECT   =>'onConnect',
            self::SCMD_ON_CLOSE         => 'onClose',
            self::SCMD_SAY          => 'say',                    //对应say方法
     );
    
    /**
     * 给特定地址的用户发送数据（GameWorker一般很少用到）
     * @param string $address gateway对应的地址
     * @param string $bin_data
     * @param integer $to_uid
     * @param integer $from_uid
     * @return boolean
     */
    public static function sendToGateway($address, $bin_data, $to_uid = 0, $from_uid = 0)
    {
        $client = stream_socket_client($address);
        $len = stream_socket_sendto($client, $bin_data);
        return $len == strlen($bin_data);
    }
    
    /**
     * 在GameWorker中使用这个方法给用户发数据
     * @param integer $uid
     * @param string $buffer
     */
    public static function sendToUid($uid, $buffer)
    {
        $address = Event::getAddressByUid($uid);
        if($address)
        {
            return self::sendToGateway($address, $buffer);
        }
        return false;
    }
    
    /**
     * 在GameWorker中用这个方法给所有用户发送数据
     * @param string $buffer
     */
    public static function sendToAll($buffer)
    {
        $data = GameBuffer::decode($buffer);
        $all_addresses = Store::get('GLOBAL_GATEWAY_ADDRESS');
        foreach($all_addresses as $address)
        {
            self::sendToGateway($address, $buffer);
        }
    }
}
