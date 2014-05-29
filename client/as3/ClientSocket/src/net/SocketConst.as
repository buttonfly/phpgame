package net
{
    /**
     * Socket 状态常量
     * @author     Yanis    
     * @date       2010-09-01
     * @version    1.0.0
     * @copyright © Yanis all rights reserved
     */
    public class SocketConst
    {
        // Socket 连接成功
        public static const  SOCKET_CONNECT:uint        = 0;
        // Socket 连接断开
        public static const  SOCKET_CLOSE:uint          = 1;
        // Socket I/O 错误
        public static const  SOCKET_IO_ERROR:uint       = 2;
        // Socket 安全沙箱冲突
        public static const  SOCKET_SECURITY_ERROR:uint = 3;
        // Socket 成功收到数据
        public static const  SOCKET_SUCCESS_DATA:uint   = 4;
    }
}