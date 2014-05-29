using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
using System.Net;
using System.Net.Sockets;

namespace ClientSocket
{
    /// <summary>
    /// 网络通讯事件模型委托
    /// </summary>
    public delegate void NetEvent(object sender, string[] e);

    public class ClientSocket
    {
        /// <summary>
        /// 客户端是否已经连接服务器
        /// </summary>
        public bool _isConnected = false;

        #region 事件定义

        /// <summary>
        /// 已经连接服务器事件
        /// </summary>
        public event NetEvent ConnectedServer;

        /// <summary>
        /// 接收到数据事件
        /// </summary>
        public event NetEvent ReceivedDatagram;

        /// <summary>
        /// 连接断开事件
        /// </summary>
        public event NetEvent DisConnectedServer;
        #endregion

        static TcpClient _client = null;

        //字节缓冲数组
        static byte[] readBuffer = new byte[64 * 1024];

        //包字段
        // 内容长度
        private int _conLen = 0;
        // 加密方式
        private int _encrypt = 0;
        private int _encryptLength = 2;
        // 指令
        private int _cmd = 0;
        private int _cmdLength = 2;
        // 包长度
        private int _packlen = 0;
        private int _packlenLength = 4;

        // 包体
        private String _body = "";

        private string[] setTemp;
        /// <summary>
        /// 默认构造函数,使用默认的编码格式
        /// </summary>
        public ClientSocket()
        {

        }

        /// <summary>
        /// 连接服务器 
        /// return true:成功 false:失败
        /// </summary>
        public virtual bool ConnectServer(string ip, int port)
        {
            if (null == _client)
                _client = new TcpClient();

            try
            {
                _client.Connect(ip, port);

                _isConnected = true;

                //触发连接建立事件
                if (ConnectedServer != null)
                {
                    ConnectedServer(this, setTemp);
                }

                NetworkStream stream = _client.GetStream();
                if (null != stream)
                {
                    stream.BeginRead(readBuffer, 0, readBuffer.Length, new AsyncCallback(ReadCallBack), null);
                }
            }
            catch (System.Exception ex)
            {
                //连接失败
                this.Close(ex.Message.ToString());
            }

            return _isConnected;
        }

        /// <summary>
        /// 发送数据报文
        /// </summary>
        /// <param name="datagram"></param>
        public virtual void Send(Int16 cmd, string datagram)
        {
            try
            {
                NetworkStream stream = _client.GetStream();
                if (datagram.Length == 0)
                {
                    return;
                }

                if (null == _client)
                {
                    throw (new ApplicationException("没有连接服务器，不能发送数据"));
                }

                //获得报文的编码字节
                ushort encrypt = 0;
                ushort _cmd = ushort.Parse(cmd.ToString());
                byte[] data_cmd = BitConverter.GetBytes(_cmd);

                byte[] data_series_id = BitConverter.GetBytes(encrypt);
                
                byte[] data_datagram = Encoding.Default.GetBytes(datagram);
                uint pack_len = uint.Parse(data_datagram.Length.ToString()) + 8;
                byte[] data_pack_len = BitConverter.GetBytes(pack_len);
                byte[] data = new byte[pack_len];

                Array.Reverse(data_cmd);
                Array.Reverse(data_series_id);
                Array.Reverse(data_pack_len);

                Buffer.BlockCopy(data_cmd, 0, data, 0, 2);
                Buffer.BlockCopy(data_series_id, 0, data, 2 * sizeof(byte), 2);
                Buffer.BlockCopy(data_pack_len, 0, data, 4 * sizeof(byte), 4);
                Buffer.BlockCopy(data_datagram, 0, data, 8 * sizeof(byte), data_datagram.Length);

                stream.BeginWrite(data, 0, data.Length, new AsyncCallback(ReadCallBack), null);
            }
            catch (System.Exception ex)
            {
                this.Close(ex.Message.ToString());
            }
        }

        /// <summary>
        /// 响应接收消息
        /// </summary>
        protected virtual void ReadCallBack(IAsyncResult ar)
        {
            try
            {
                NetworkStream stream = _client.GetStream();
                if (null != stream)
                {
                    int numberOfBytesRead = stream.EndRead(ar);
                    if (numberOfBytesRead > 0)
                    {
                        string stringData = Encoding.UTF8.GetString(readBuffer, 0, numberOfBytesRead);

                        string[] sData = Resolve(ref readBuffer);
                        ReceivedDatagram(this, sData);

                        //继续读取数据
                        stream.BeginRead(readBuffer, 0, readBuffer.Length, new AsyncCallback(ReadCallBack), null);

                    }
                    else
                    {
                        ReceivedDatagram(this, setTemp);
                    }
                }
            }
            catch (System.Net.Sockets.SocketException ex)
            {
                this.Close(ex.Message.ToString());
            }
            catch (System.Exception ex)
            {

            }
        }

        /// <summary>
        /// 关闭连接
        /// </summary>
        public virtual void Close(string message)
        {
            if (_client != null)
            {
                _client.Close();
                _client = null;
            }

            //清理资源
            //_client.Close();
            String[] _ex = { "", "", message };
            DisConnectedServer(this, _ex);
            _isConnected = false;
        }

        /// <summary>
        /// 解析报文
        /// </summary>
        /// <param name="rawDatagram">原始数据,返回未使用的报文片断,
        /// 该片断会保存在Session的Datagram对象中</param>
        /// <returns>报文数组,原始数据可能包含多个报文</returns>
        private int _step = 0;
        public virtual string[] Resolve(ref byte[] rawDatagram)
        {
            ArrayList datagrams = new ArrayList();

            //末尾标记位置索引
            bool whileBreak = false;
            int indexFrom = 0;
            while (true)
            {
                int byteLen = rawDatagram.Length;
                switch (this._step)
                {
                    case 0:
                        // 读取命令
                        byte[] temp = new byte[_cmdLength];
                        Array.Copy(rawDatagram, 0, temp, 0, _cmdLength);
                        Array.Reverse(temp);
                        _cmd = System.BitConverter.ToInt16(temp, 0);
                        datagrams.Add(_cmd.ToString());
                        this._step++;
                        break;
                    case 1:
                        // 读取加密方式
                        byte[] temp1 = new byte[_encryptLength];
                        indexFrom = _cmdLength;
                        Array.Copy(rawDatagram, indexFrom, temp1, 0, _encryptLength);
                        Array.Reverse(temp1);
                        _encrypt = System.BitConverter.ToInt16(temp1, 0);
                        datagrams.Add(_encrypt.ToString());
                        this._step++;
                        break;
                    case 2:
                        // 读取包长度
                        byte[] temp2 = new byte[_packlenLength];
                        indexFrom = _cmdLength + _encryptLength;
                        Array.Copy(rawDatagram, indexFrom, temp2, 0, _packlenLength);
                        Array.Reverse(temp2);
                        _packlen = System.BitConverter.ToInt32(temp2, 0);
                        this._conLen = _packlen - 8;
                        this._step++;
                        break;
                    case 3:
                        // 读取包体
                        if (this._conLen > 0)
                        {
                            byte[] temp3 = new byte[this._conLen];
                            indexFrom = 8;
                            Array.Copy(rawDatagram, indexFrom, temp3, 0, this._conLen);
                            _body = Encoding.UTF8.GetString(temp3, 0, temp3.Length);
                            Array.Clear(rawDatagram, 0, rawDatagram.Length);
                            datagrams.Add(_body);
                            this._step = 0;
                            whileBreak = true;
                        }
                        else
                        {
                            Array.Clear(rawDatagram, 0, rawDatagram.Length);
                            this._step = 0;
                            whileBreak = true;
                        }
                        break;

                }
                if (whileBreak)
                {
                    break;
                }
            }

            string[] results = (string[])datagrams.ToArray(typeof(string));
            return results;
        }
    }
}