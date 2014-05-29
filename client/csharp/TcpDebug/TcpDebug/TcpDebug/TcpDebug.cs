using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.IO;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;
using System.Net;
using System.Net.Sockets;
using ClientSocket;
using Newtonsoft.Json;

namespace TcpDebug
{
    public partial class TcpDebug : Form
    {
        private delegate void richTextBoxCallBack();
        private delegate void buttonCallBack();
        private delegate void timerCallBack(); 


        String sInput = "";

        ClientSocket.ClientSocket conn = new ClientSocket.ClientSocket();

        public TcpDebug()
        {
            InitializeComponent();
            
        }

        private void TcpDebug_Load(object sender, EventArgs e)
        {
            conn.ReceivedDatagram += new ClientSocket.NetEvent(this.RecvData);
            conn.DisConnectedServer += new ClientSocket.NetEvent(this.ClientClose);
            conn.ConnectedServer += new ClientSocket.NetEvent(this.ClientConn);
        }

        private void label1_Click(object sender, EventArgs e)
        {

        }

        private void button2_Click(object sender, EventArgs e)
        {
            bool Connected = conn.ConnectServer(textBox1.Text.Trim(), int.Parse(textBox2.Text.Trim()));
            if (Connected)
            {
                button3.Visible = true;
                button1.Enabled = true;
                button2.Enabled = false;
                button3.Enabled = true;
                timer1.Enabled = true;
                toolStripStatusLabel1.Text = "启动";
            }
            else {

            }
        }

        private void button3_Click(object sender, EventArgs e)
        {
            string message = "用户关闭了连接";
            conn.Close(message);
            button3.Visible = false;
            button1.Enabled = false;
            button2.Enabled = true;
            button3.Enabled = false;
        }

        private void button1_Click(object sender, EventArgs e)
        {
            String datagram = "";
            
            StringBuilder sb = new StringBuilder();
            StringWriter sw = new StringWriter(sb);
            using (JsonWriter writer = new JsonTextWriter(sw))
            {
                writer.Formatting = Formatting.Indented;
                
                writer.WriteStartObject();
                writer.WritePropertyName("cmd");
                writer.WriteValue(textBox5.Text.Trim());
                writer.WritePropertyName("scmd");
                writer.WriteValue(textBox6.Text.Trim());
                writer.WritePropertyName("data");
                writer.WriteStartArray();
                if (null != textBox3.Text.Trim())
                {
                    string[] Jarray = textBox3.Text.Trim().Split(new string[] { "\r\n" }, StringSplitOptions.None);
                    foreach (string Jvalue in Jarray)
                    {
                        writer.WriteValue(Jvalue);
                    }
                }
                
                writer.WriteEnd();
                writer.WriteEndObject();
            }
            datagram = sb.ToString();
            conn.Send(Int16.Parse(textBox8.Text.Trim()), datagram);
            sInput = ">>>>>> 发送数据包：" + datagram + "\r\n";
            richTextBox1.SelectionColor = Color.Goldenrod;
            richTextBox1.AppendText(sInput);
            richTextBox1.SelectionStart = richTextBox1.Text.Length;
            richTextBox1.ScrollToCaret();
        }

        void ClientConn(object sender, String[] e)
        {
            String datagram = "";
            if (e != null)
            {
                datagram = e[2].ToString();
            }
            sInput = "成功连接服务器" + textBox1.Text.Trim() + ", 端口" + textBox2.Text.Trim() + datagram + "\r\n";
            SetText(sInput, Color.Green);
        }

        void ClientClose(object sender, String[] e)
        {
            String datagram = "";
            if (e != null)
            {
                try
                {
                    datagram = e[2].ToString();
                }
                catch (Exception ex)
                {
                }
            }
            sInput = "成功关闭连接!" + datagram + "\r\n";
            SetText(sInput, Color.Blue);
            timer1.Enabled = false;
            toolStripStatusLabel1.Text = "停止";
            SetButtonClone();
        }

        void RecvData(object sender, String[] e)
        {
            String datagram = "";
            if (e != null)
            {
                try
                {
                    datagram = e[2].ToString();
                } catch (Exception ex) {
                }
            }
            sInput = "<<<<<< 接收到数据包：" + datagram + "\r\n";
            SetText(sInput, Color.HotPink);
        }

        public void SetText(string sInput, Color wordColor)
        {
            richTextBoxCallBack callback = delegate()//使用委托 
            {
                richTextBox1.SelectionColor = wordColor;
                richTextBox1.AppendText(sInput);
                richTextBox1.SelectionStart = richTextBox1.Text.Length;
                richTextBox1.ScrollToCaret();
            };

            richTextBox1.Invoke(callback);
        }

        public void SetButtonClone()
        {
            buttonCallBack callback = delegate()//使用委托 
            {
                button3.Visible = false;
                button1.Enabled = false;
                button2.Enabled = true;
                button3.Enabled = false;
                label10.Text = "60"; 
            };
            button3.Invoke(callback);
            button2.Invoke(callback);
            button1.Invoke(callback);
            label10.Invoke(callback);
            // 
        }

        public void SetTimer()
        {
            timerCallBack callback = delegate()//使用委托 
            {
                button3.Visible = false;
                button1.Enabled = false;
                button2.Enabled = true;
                button3.Enabled = false;

            };
            // timer1.Invoke(callback);
        }

        private void timer1_Tick(object sender, EventArgs e)
        {
            if (int.Parse(label10.Text.ToString()) <= 0)
            {
                label10.Text = "60";
                String datagram = "";

                StringBuilder sb = new StringBuilder();
                StringWriter sw = new StringWriter(sb);
                using (JsonWriter writer = new JsonTextWriter(sw))
                {
                    writer.Formatting = Formatting.Indented;

                    writer.WriteStartObject();
                    writer.WritePropertyName("heart");
                    writer.WriteValue("1");
                    writer.WriteEndObject();
                }
                datagram = sb.ToString();
                conn.Send(9, datagram);
                sInput = ">>>>>> 发送心跳数据包：" + datagram + "\r\n";
                richTextBox1.SelectionColor = Color.Brown;
                richTextBox1.AppendText(sInput);
                richTextBox1.SelectionStart = richTextBox1.Text.Length;
                richTextBox1.ScrollToCaret();
            }
            else 
            {
                int waitTime = int.Parse(label10.Text.ToString()) ;
                label10.Text = (waitTime - 1).ToString();
            }
        }

        private void linkLabel1_LinkClicked(object sender, LinkLabelLinkClickedEventArgs e)
        {
            System.Diagnostics.Process.Start("www.phpgame.cn");
        }
    }
}
