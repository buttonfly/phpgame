package net
{
    //import com.adobe.serialization.json.JSON;
    import flash.utils.ByteArray;
    
    /**
     * Socket事件
     * @author        Yanis
     * @date        2010-05-23
     * @version        1.0.0
     * @copyright    © Yanis all rights reserved
     */
    public class SocketEvent
    {
        // 协议头
        private var _head:uint = 0;
        // 协议体
        private var _body:ByteArray = null;
        
        public function SocketEvent()
        {

        }
        // 设置协议头
        public function setHead( head:uint ):void
        {
            this._head = head;
        }
        // 设置内容
        public function setBody( body:ByteArray ):void
        {
            this._body = body;
        }
        // 获取协议头
        public function getHead():uint
        {
            return _head;
        }
        
        // 获取协议体
        public function getBody():ByteArray
        {
            return _body;
        }
    }
}