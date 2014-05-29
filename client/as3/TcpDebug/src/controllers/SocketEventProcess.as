package controllers
{
    import flash.events.Event;
    import flash.events.SecurityErrorEvent;
    import flash.net.Socket;
    import flash.system.Security;
    import flash.utils.ByteArray;
    
    import net.ClientSocket;
    import net.*;
    import view.UIOutputArea;
    
    /**
     * Socket事件处理类
     * @author            Yanis
     * @version            1.0.0
     * @date            2012-1-23 下午04:37:08
     */
    public class SocketEventProcess
    {
        private static var _instance:SocketEventProcess;
        /** Server响应对象 */
        private var _servReceive:ServerReceive;
        
        public function SocketEventProcess()
        {
            _servReceive = ServerReceive.getInstance();
        }
        public static function getInstance():SocketEventProcess
        {
            if(null == _instance)
                _instance = new SocketEventProcess();
            return _instance;
        }
        /**
         * 连接 Server
         */
        public function connect( ip:String, port:uint ):void
        {
            UIOutputArea.getInstance().append('正在与Server建立连接... ip:'+ip+' port:'+port);
            ClientSocket.connect(ip, port);
        }
        
        /**
         * 发送协议数据
         */
       public function send( data:ByteArray ):void
        {
            var len:uint = data.length;
            var bytesStr:String = '';
            for(var i:uint=0; i<len; i++){
                trace(data[i]);
                bytesStr += data[i]+',';
            }
            UIOutputArea.getInstance().append('发送的字节内容:'+bytesStr);
            
            //data = AESCrypto.encryptByteArray( KeyManager.key, data, KeyManager.iv );
            ClientSocket.send(data);
        }
        
        public function read(cmd:int, data:String):ByteArray
        {
            var sender:ByteArray = new ByteArray();
            sender.writeShort(cmd);
            sender.writeShort(0);
            var len:int = 8 + data.length;
            sender.writeUnsignedInt(len);
            sender.writeUTFBytes(data);
            // trace('长度' + content.length);
            return sender;
        }
        
        public function close():void
        {
            ClientSocket.close();
        }
    }
}