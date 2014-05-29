package view
{
    import controllers.*;
    
    import flash.events.MouseEvent;
    
    import mx.containers.*;
    import mx.controls.Button;
    import mx.controls.Label;
    import mx.controls.TextInput;
    import mx.core.UIComponent;
    
    /**
     * 连接面板
     * @author            Yanis
     * @version            1.0.0
     * @date            2011-12-7 下午03:01:05
     */
    public class UIConnect extends Canvas
    {
        //IP输入框
        private var _ipTxt:TextInput;
        //端口输入框
        private var _portTxt:TextInput;
        public static var connectBtn:Button;
        
        public function UIConnect()
        {
            init();
        }
        private function init():void
        {
            var label0:Label = new Label();
            label0.text = 'IP:';
            label0.y = 20;
            label0.x = 30;
            
            _ipTxt = new TextInput();
            _ipTxt.x = 60;
            _ipTxt.y = 20;
            _ipTxt.width = 130;
            _ipTxt.text = "1.82.191.8";
            
            var label1:Label = new Label();
            label1.text = 'port:';
            label1.x = _ipTxt.x + _ipTxt.width + 40;
            label1.y = 20;
            
            _portTxt = new TextInput();
            _portTxt.x = label1.x + 30 + 10;
            _portTxt.y = 20;
            _portTxt.width = 40;
            _portTxt.text = "8282";
            
            connectBtn = new Button();
            connectBtn.label = '连接';
            connectBtn.y = 20;
            connectBtn.x = _portTxt.x + _portTxt.width + 5 + 30;
            connectBtn.addEventListener( MouseEvent.MOUSE_UP, connectHandler );
            
            this.addChild(label0);
            this.addChild(_ipTxt);
            this.addChild(label1);
            this.addChild(_portTxt);
            this.addChild(connectBtn);
        }
        private function connectHandler( evt:MouseEvent ):void
        {
            var ip:String = _ipTxt.text;
            var port:uint = uint(_portTxt.text);
            SocketEventProcess.getInstance().connect(ip, port);
        }
    }
}