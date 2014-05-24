<?php 
/**
 * 二进制协议
 * 
 * struct BufferProtocol
 * {
 *     unsigned short    cmd,          //主命令字，预留
 *     unsigned short    encrypt,      //加密情况 udp协议使用
 *     unsigned int      pack_len,     //包长
 *     char[pack_length-HEAD_LEN] body //包体
 * }
 * 
 * @author walkor <worker-man@qq.com>
 */

class Buffer
{
    /**
     * 包头长度
     * @var integer
     */
    const HEAD_LEN = 8;
     
    /**
     * 加密类型
     * @var integer
     */
    protected static $encrypt = 0;
    
    /**
     * 协议头
     * @var array
     */
    public $header = array(
        'cmd'      => 0,
        'encrypt'  => 0,
        'pack_len' => self::HEAD_LEN
    );
    
    /**
     * 包体
     * @var string
     */
    public $body = '';
    
    /**
     * 初始化
     * @return void
     */
    public function __construct($buffer = null)
    {
        if ($buffer) {
            $data = self::decode($buffer);
            $this->body = $data['body'];
            unset($data['body']);
            $this->header = $data;
        }
    }
    
    /**
     * 判断数据包是否都到了
     * @param string $buffer
     * @return int int=0数据是完整的 int>0数据不完整，还要继续接收int字节
     */
    public static function input($buffer, &$data = null)
    {
        $len = strlen($buffer);
        if ($len < self::HEAD_LEN) {
            return self::HEAD_LEN - $len;
        }
        
        // $data = unpack("Cversion/nseries_id/ncmd/nsub_cmd/Ncode/Nfrom_uid/Nto_uid/Npack_len", $buffer);
        $data = unpack("ncmd/nencrypt/Npack_len", $buffer);
        if ($data['pack_len'] > $len) {
            return $data['pack_len'] - $len;
        }
        $data['body'] = '';
        $body_len = $data['pack_len'] - self::HEAD_LEN;
        if ($body_len > 0) {
            $data['body'] = substr($buffer, self::HEAD_LEN, $body_len);
        }
        return 0;
    }
    
    
    /**
     * 设置包体
     * @param string $body_str
     * @return void
     */
    public function setBody($body_str)
    {
        $this->body = (string) $body_str;
    }
    
    /**
     * 获取整个包的buffer
     * @param string $data
     * @return string
     */
    public function getBuffer()
    {
        $this->header['pack_len'] = self::HEAD_LEN + strlen($this->body);
        return pack("nnN", $this->header['cmd'], $this->header['encrypt'], $this->header['pack_len']).$this->body;
    }
    
    /**
     * 从二进制数据转换为数组
     * @param string $buffer
     * @return array
     */    
    public static function decode($buffer)
    {
        $data = unpack("ncmd/nencrypt/Npack_len", $buffer);
        $data['body'] = '';
        $body_len = $data['pack_len'] - self::HEAD_LEN;
        if ($body_len > 0) {
            $data['body'] = substr($buffer, self::HEAD_LEN, $body_len);
        }
        return $data;
    }
    
}
