package controllers
{
    import flash.utils.ByteArray;
    import net.*;
    import view.*;
    
    /**
     * 响应Server返回信息
     * @author            Yanis
     * @date            2011-02-26
     * @version            v1.0
     */
    public class ServerReceive
    {
        private static var _instance:ServerReceive;
        
        
        public function ServerReceive()
        {
            ClientSocket.setProcess(socketStatus);
        }
        
        public static function getInstance():ServerReceive
        {
            if(null == _instance)
                _instance = new ServerReceive();
            return _instance;
        }
        
        /**
         * 响应Socket状态
         * @param    status        socket状态值
         * @param    socketData    返回的数据
         */
        public function socketStatus( status:uint, socketData:SocketEvent = null):void
        {
            trace('返回状态');
            trace(status);
            switch ( status )
            {
                case SocketConst.SOCKET_CONNECT         :    onConnect();                break;    //socket 连接成功
                case SocketConst.SOCKET_CLOSE            :    onClose();                    break;    //socket 中断
                case SocketConst.SOCKET_IO_ERROR        :    ioError();                    break;    //socket I/O错误
                case SocketConst.SOCKET_SECURITY_ERROR    :    securityError();            break;    //socket 安全策略错误
                case SocketConst.SOCKET_SUCCESS_DATA    :    onSuccess( socketData );    break;  //收到协议
            }
        }
        
        /**
         * socket 连接成功
         */
        private function onConnect():void
        {
            trace("Socket连接成功");
            UIOutputArea.getInstance().append('与服务器连接成功!');
            this.setOpen();
        }
        
        /**
         * socket 中断
         */
        private function onClose():void
        {
            trace("Socket连接中断");
            UIOutputArea.getInstance().append('与服务器连接中断!');
            this.setClose();
        }
        
        /**
         * socket I/O错误
         */
        private function ioError():void
        {
            trace("Socket I/O错误**");
            UIOutputArea.getInstance().append('连接服务器超时!');
            this.setClose();
        }
        
        /**
         * socket 安全策略错误
         */
        private function securityError():void
        {
            trace("Socket 安全策略错误");
            UIOutputArea.getInstance().append('安全策略错误!');
            this.setClose();
        }
        
        /**
         * 关闭连接按钮，同时打开其他按钮
         */
        private function setClose():void
        {
            UIParamArea.timer.stop();
            UIParamArea.closeBtn.enabled = false;
            UIConnect.connectBtn.enabled = true;
            UIParamArea.sendBtn.enabled = false;
        }
        
        /**
         * 打开连接按钮，同时关闭其他按钮
         */
        private function setOpen():void
        {
            UIParamArea.timer.start();
            UIParamArea.closeBtn.enabled = true;
            UIConnect.connectBtn.enabled = false;
            UIParamArea.sendBtn.enabled = true;
        }
        
        /**
         * 接收到Server协议
         */
        private function onSuccess( socketData:SocketEvent ):void
        {
            var ProName:uint = socketData.getHead();
            var data:ByteArray = socketData.getBody();
            var s:String = data.readMultiByte(data.length,"UTF-8");
            UIOutputArea.getInstance().append('>>>>>>>>>>收到Server协议:'+ProName+'<<<<<<<<<<<<');
            UIOutputArea.getInstance().append( s );
            UIOutputArea.infoTxt.verticalScrollPosition = UIOutputArea.infoTxt.maxVerticalScrollPosition;
            switch ( ProName ) {
                case 0: onError(data);   break; //服务器出错返回
                case 1: onFailure(data); break; //失败返回
            }
            trace("TCP协议返回:"+ProName);
        }

        /**
         * 失败返回
         */
        private function onFailure( data:ByteArray ):void
        {
            trace("失败返回");
        }
        
        /**
         * 服务嚣出错返回
         */
        private function onError( data:ByteArray ):void
        {
            trace("服务器出错返回");
        }
    }
}