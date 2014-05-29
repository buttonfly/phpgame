package util
{
    import flash.utils.ByteArray;
    
    /**
     * 缓冲数组类ByteArray
     * @author  Yanis
     * @date    2010-09-17
     * @version v1.0
     */
    public class BytesArray extends ByteArray
    {
        // 尾指针
        private var lastPos:uint    = 0;
        
        /**
         * 删除指定区间字节数据
         * 
         * @param start uint 开始
         * @param end   uint 结束
         */
        private function delBytes( start:uint, end:uint ):void
        {
            var i:uint=0,j:uint=0;
            for(i=start,j=end; j<lastPos; i++,j++){
                this[i] = this[j];
            }
            lastPos = i;
        }
        
        /**
         * 向缓冲数组追加字节
         * 
         * @param bytes ByteArray 数据流
         */
        public function appendBytes( bytes:ByteArray ):void
        {
            this.position = lastPos;
            this.writeBytes( bytes );
            lastPos = this.position;
        }
        
        /**
         * 返回缓冲区中字节数
         * 
         * @return lastPos mixed 最后的位置
         */
        public function getLength():uint
        {
            return lastPos;
        }
        
        /**
         * 读byte
         * 
         * @return byte ByteArray 字节
         */
        public override function readByte():int
        {
            var byte:int = 0;
            this.position = 0;
            byte = super.readByte();
            delBytes(0,1);
            return byte;
        }
        
        /**
         * 读short
         * 
         * @return short short 短整型
         */
        public override function readShort():int
        {
            var short:int = 0;
            this.position = 0;
            short = super.readShort();
            delBytes(0,2);
            return short;
        }
        
        /**
         * 读int
         * 
         * @return i int 长整型
         */
        public override function readInt():int
        {
            var i:int = 0;
            this.position = 0;
            i = super.readInt();
            delBytes(0,4);
            return i;
        }
        
        /**
         * 读指定字节数
         * 
         * @return bArr ByteArray 字节流
         */
        public function readBufBytes( n:uint ):ByteArray
        {
            if( n > lastPos ) return null;
            this.position = 0;
            var bArr:ByteArray = new ByteArray();
            for(var i:uint=0; i<n; i++){
                bArr[i] = this[i];
            }
            delBytes(0,n);
            return bArr;
        }
        
        /**
         * 读urf-8 string
         * 
         * @return s String 字符串
         */
        public function readUTFString( n:uint ):String
        {
            if( n > lastPos ) return "";
            var s:String = "";
            this.position = 0;
            var tmArr:ByteArray = new ByteArray();
            for(var i:uint=0; i<n; i++)
                tmArr[i] = this[i];
            s = tmArr.toString();
            delBytes(0,n);
            return s;
        }
        
        /**
         * 打印缓冲区字节
         */
        public function printBytes():void
        {
            for(var i:uint=0; i<lastPos; i++)
                trace(this[i]);
        }
    }
}