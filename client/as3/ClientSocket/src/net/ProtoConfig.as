package net
{
    /**
     * tcp协议基本常量
     * @author            Yanis
     * @date            2010-09-02
     * @version            v1.0
     */
    public class ProtoConfig
    {
        // 整形数据的字节长度
        public static const INT_LENGTH:uint          = 4;
        // 短整型数据的字节长度
        public static const SHORT_LENGTH:uint        = 2;
        // 字符型数据的字节长度
        public static const CHAR_LENGTH:uint         = 1;
        // 无符号字符型的最大值
        public static const MAX_CHAR_VALUE:uint      = 255;
        // 无符号短整型的最大值
        public static const MAX_SHORT_VALUE:uint     = 65535;
        // 无符号整型的最大值
        public static const MAX_INT_VALUE:uint       = uint.MAX_VALUE;
        // 半个字节的位数长度
        public static const HALF_CHAR_LENGTH:uint    = 4;
        // 单字节高四位最大值
        public static const MAX_HIGH_CHAR_VALUE:uint = 0xF0;
        // 单字节低四位最大值
        public static const MAX_LOW_CHAR_VALUE:uint  = 0x0F;
        // 单字节低四位最小值
        public static const MIN_LOW_CHAR_VALUE:uint  = 0x00;
    }
}