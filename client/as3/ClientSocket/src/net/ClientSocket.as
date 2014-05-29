package net
{
    import flash.errors.*;
    import flash.events.*;
    import flash.system.Security;
    import flash.net.Socket;
    import flash.utils.ByteArray;
    import net.ProtoConfig;
    import util.BytesArray;
    
    /**
     * 客户端socket类 ClientSocket
     * @author  Yanis
     * @date    2010-09-17
     * @version v1.0
     */
    public class ClientSocket
    {
        private static var _byteBuff:BytesArray;
        private static var _buffTmp:ByteArray;
        
        // 协议长度
        private static var _proLen:uint = 0;
        // 内容长度
        private static var _conLen:uint = 0;
        // 序列号
        private static var _seriesid:uint = 0;
        // 命令
        private static var _cmd:uint = 0;
        // 包长度
        private static var _packlen:uint = 0;
        
        private static var _socketEvt:SocketEvent = null;
        private static var _eventProcess:Function = null;
        
        /**
         * 初始化
         */
        public function ClientSocket()
        {
            if (null == _socket) {
                _socket = new Socket();
                _byteBuff = new BytesArray();
                _buffTmp  = new ByteArray();
                _socketEvt = new SocketEvent();
                configureListeners();
            }
        }
        
        private static var _socket:Socket = null;
        
        /**
         * 连接服务器
         * 
         * @param host String IP地址
         * @param port int    端口
         */
        public static function connect(host:String, port:int):void
        {
            if (null == _socket) {
                _socket = new Socket();
                _byteBuff = new BytesArray();
                _buffTmp  = new ByteArray();
                _socketEvt = new SocketEvent();
                configureListeners();
            }
            
            Security.loadPolicyFile("xmlsocket://" + host + ":843");
            _socket.connect(host, port);
        }
        
        /**
         * 连接成功
         * 
         * @param event Event 事件
         */
        private static function connectHandler(event:Event):void
        {
            if ( typeof(_eventProcess) == "function" )
                _eventProcess( SocketConst.SOCKET_CONNECT );
        }
        
        /**
         * 连接关闭
         * 
         * @param event Event 事件
         */
        private static function closeHandler(event:Event):void
        {
            if ( typeof(_eventProcess) == "function" )
                _eventProcess( SocketConst.SOCKET_CLOSE );
        }
        
        /**
         * IO错误
         * 
         * @param event IOErrorEvent 事件
         */
        private static function ioErrorHandler(event:IOErrorEvent):void 
        {
            if ( typeof(_eventProcess) == "function" )
                _eventProcess( SocketConst.SOCKET_IO_ERROR );
        }
        
        /**
         * 安全策略错误
         * 
         * @param event SecurityErrorEvent 错误事件
         */
        private static function securityErrorHandler(event:SecurityErrorEvent):void
        {
            if ( typeof(_eventProcess) == "function" )
                _eventProcess( SocketConst.SOCKET_SECURITY_ERROR );
        }
        
        /**
         * 发数据
         * 
         * @param s String 客户单的打印数据
         */
        public static function sendUTF(s:String):void
        {
            _socket.writeUTF(s);
        }
        
        /**
         * 刷新缓冲区
         */
        public static function flush():void
        {
            _socket.flush();
        }
        
        /**
         * 发送消息
         * @param    data    要发送的字节数据
         */
        public static function send(data:ByteArray):void
        {
            if (_socket.connected) {
                _socket.writeBytes(data);
                flush();
            }
        }
        
        /**
         * 关闭连接
         */
        public static function close():void
        {
            if (_socket.connected) {
                // _socket = null;
                _socket.close();
                var evt:Event;
                closeHandler(evt);
            }
        }
        
        /**
         * 数据返回
         */
        private static function socketDataHandler(event:ProgressEvent):void
        {
            if ( typeof(_eventProcess) == "function" )
                onData();
        }
        
        /**
         * 设置外部数据处理函数
         * 
         * @param  process Function 要发送的字节数据
         */
        public static function setProcess( process:Function ):void
        {
            _eventProcess = process;
        }
        
        // 解析 Server返回的协议数据
        private static var _step:uint=0;
        private static function onData():void
        {
            _buffTmp.length = 0;
            _socket.readBytes( _buffTmp );
            _byteBuff.appendBytes( _buffTmp );
            
            var byteLen:uint = 0;
            
            L:while (true) {
                byteLen = _byteBuff.getLength();
                var len:uint = byteLen;
                var bytesStr:String = '';
                for (var i:uint=0; i<len; i++) {
                    bytesStr += _byteBuff[i]+',';
                }
                switch (_step) {
                    case 0:
                        if ( byteLen >= ProtoConfig.SHORT_LENGTH ) {
                            // 读取命令
                            _cmd = _byteBuff.readShort();
                            _step++;
                        } else {
                            break L;
                        }
                        break;
                    case 1:
                        if ( byteLen >= ProtoConfig.SHORT_LENGTH ) {
                            // 读取序列号
                            _seriesid = _byteBuff.readShort();
                            _step++;
                        } else {
                            break L;
                        }
                        break;
                    case 2:
                        if ( byteLen >= ProtoConfig.INT_LENGTH ) {
                            // 读取包长度
                            _packlen = _byteBuff.readInt();
                            _conLen = _packlen - 8;
                            _step++;
                        } else {
                            break L;
                        }
                        break;
                    case 3:
                        if ( byteLen >= _conLen ) {
                            // 读取包体
                            var body:ByteArray = _byteBuff.readBufBytes( _conLen );
                            _socketEvt.setHead( _cmd );
                            _socketEvt.setBody( body );
                            _eventProcess( SocketConst.SOCKET_SUCCESS_DATA, _socketEvt);
                            _step = 0;
                        } else {
                            break L;
                        }
                        break;
                }
            }
        }
        
        /**
         * 注册监听器
         * 
         * @param  process Function 要发送的字节数据
         */
        private static function configureListeners():void
        {
            _socket.addEventListener(Event.CLOSE, closeHandler);
            _socket.addEventListener(Event.CONNECT, connectHandler);
            _socket.addEventListener(IOErrorEvent.IO_ERROR, ioErrorHandler);
            _socket.addEventListener(SecurityErrorEvent.SECURITY_ERROR, securityErrorHandler);
            _socket.addEventListener(ProgressEvent.SOCKET_DATA, socketDataHandler);
        }
    }
}