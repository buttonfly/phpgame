package view
{
    import controllers.SocketEventProcess;
    
    import flash.events.MouseEvent;
    import flash.events.TimerEvent;
    import flash.utils.ByteArray;
    import flash.utils.Timer;
    
    import json.*;
    
    import mx.containers.Canvas;
    import mx.controls.Button;
    import mx.controls.Label;
    import mx.controls.TextArea;
    
    /**
     * 发送参数填写区
     * @author            Yanis
     * @version            1.0.0
     * @date            2011-12-7 下午03:26:13
     */
    public class UIParamArea extends Canvas
    {
        // private var _container:Canvas;
        private var _container:TextArea;
        private var _container2:TextArea;
        private var _container3:TextArea;
        private var _container12:TextArea;
        private var _container13:TextArea;
        private var _label3:Label;
        public static var timer:Timer;
        public static var closeBtn:Button;
        public static var sendBtn:Button;
        /** 当前显示的参数面板 */
        
        public function UIParamArea()
        {
            init();
        }
        private function init():void
        {
            var label05:Label = new Label();
            label05.text = '加密方式:';
            label05.x = 20;
            label05.y = 25;
            this.addElement(label05);
            
            _container2 = new TextArea(); // new Canvas();
            _container2.y = 25;
            _container2.x = 20 + 20 + 20;
            _container2.width = 60;
            _container2.height = 20;
            _container2.setStyle('borderStyle', 'solid');
            _container2.text = "1";
            this.addElement(_container2);
            
            var label06:Label = new Label();
            label06.text = '预留:';
            label06.x = 20 + 20 + 15 + 60 + 20;
            label06.y = 25;
            this.addElement(label06);
            
            _container3 = new TextArea(); // new Canvas();
            _container3.y =  25;
            _container3.x = 100 + 10 + 20 + 50;
            _container3.width = 60;
            _container3.height = 20;
            _container3.setStyle('borderStyle', 'solid');
            _container3.text = "0";
            this.addElement(_container3);
            
            var label11:Label = new Label();
            label11.text = '父命令';
            label11.x = 20;
            label11.y = 55;
            this.addElement(label11);
            
            _container12 = new TextArea(); // new Canvas();
            _container12.y = 55 ;
            _container12.x = 20 + 20 + 20;
            _container12.width = 60;
            _container12.height = 20;
            _container12.setStyle('borderStyle', 'solid');
            _container12.text = "1";
            this.addElement(_container12);
            
            var label12:Label = new Label();
            label12.text = '子命令';
            label12.x = 80 + 60;
            label12.y = 55;
            this.addElement(label12);
            
            _container13 = new TextArea(); // new Canvas();
            _container13.y =  55;
            _container13.x = 180;
            _container13.width = 60;
            _container13.height = 20;
            _container13.setStyle('borderStyle', 'solid');
            _container13.text = "1";
            this.addElement(_container13);
            
            var label0:Label = new Label();
            label0.text = '数据包';
            label0.x = 20;
            label0.y = 85;
            this.addElement(label0);
            
            _container = new TextArea(); // new Canvas();
            _container.y = 85;
            _container.x = 60;
            _container.width = 360;
            _container.height = 180;
            _container.setStyle('borderStyle', 'solid');
            this.addElement(_container);
            //发送按钮
            sendBtn = new Button();
            sendBtn.y = _container.y + _container.height + 5;
            sendBtn.x = 60;
            sendBtn.label = '发送';
            sendBtn.enabled = false;
            this.addElement(sendBtn);
            sendBtn.addEventListener(MouseEvent.MOUSE_UP, sendHandler);
            
            var label2:Label = new Label();
            label2.text = '心跳包发送倒计时：';
            label2.x = 60;
            label2.y = sendBtn.y + 50;
            this.addElement(label2);
            
            _label3 = new Label();
            _label3.text = '60';
            _label3.x = 170;
            _label3.y = sendBtn.y + 50;
            this.addElement(_label3);
            
            closeBtn = new Button();
            closeBtn.y = label2.y + label2.height + 30;
            closeBtn.x = 60;
            closeBtn.label = '断开连接';
            closeBtn.enabled = false;
            this.addElement(closeBtn);
            closeBtn.addEventListener(MouseEvent.MOUSE_UP, closeHandler);
            
            timer = new Timer(1000, 0);//实例化Timer,第一个参数是触发的时间间隔,第二个参数是要触的总次数,如果是5,触发5次扣会自动停止,如果为0则是无限次的 
            timer.addEventListener(TimerEvent.TIMER, timerEventHandler);//给timer添加TimerEvent.TIMER事件帧听器. 
            // timer.start();//计时器开始执行 
            //GDispatcher.getInstance().addEventListener( UIEventConst.UI_CHANGE_PROTOCOL, changeProtocol );
        }
        
        //timer的TimerEvent.TIMER事件处理函数 
        private function timerEventHandler(e:TimerEvent):void
        {
            if (parseInt(_label3.text) <= 0) {
                var _obj:Object = {};
                _obj.cmd = 8;
                _obj.scmd = 8;
                _obj.data = ["1"];
                var sendNote:String = json.JSON.encode(_obj);
                trace(sendNote);
                var cmd:int = 1;
                var sender : ByteArray = SocketEventProcess.getInstance().read(cmd, sendNote);
                SocketEventProcess.getInstance().send(sender);
                _label3.text = "60";
            } else {
                var temint:int = parseInt(_label3.text);
                temint--;
                _label3.text = temint.toString();
            }
        }
        
        private function sendHandler( evt:MouseEvent ):void
        {
            // json.JSON.encode();
            var _obj:Object = {};
            var _arr:Array = [];
            if("" != _container.text && "" != _container2.text && "" != _container3.text && _container12.text!="" && _container13.text != ""){
                // _uiParamImpl.send();
                _obj.cmd = _container12.text.toString();
                _obj.scmd = _container13.text.toString();
                _arr = _container.text.toString().split("\r");
                for (var s:String in _arr) {
                    trace(_arr[s]);
                }
                _obj.data = [];
                _obj.data = _arr;
                var sendNote:String = json.JSON.encode(_obj);
                trace(sendNote);
                var cmd:int = parseInt(_container2.text);
                //var subcmd:int = parseInt(_container3.text);
                var sender : ByteArray = SocketEventProcess.getInstance().read(cmd, sendNote);
                SocketEventProcess.getInstance().send(sender);
            }
        }
        
        private function closeHandler( evt:MouseEvent ):void
        {
            SocketEventProcess.getInstance().close();
        }
    }
}