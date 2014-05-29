package view
{
    import mx.containers.Canvas;
    import mx.controls.Label;
    import mx.controls.TextArea;
    
    /**
     * 输出信息区
     * @author            Yanis
     * @version            1.0.0
     * @date            2011-12-7 下午03:36:46
     */
    public class UIOutputArea extends Canvas
    {
        private static var _uiOutputArea:UIOutputArea;
        /** 信息框 */
        public static var infoTxt:TextArea;
        
        public function UIOutputArea()
        {
            init();
        }
        public static function getInstance():UIOutputArea
        {
            if(null == _uiOutputArea)
                _uiOutputArea = new UIOutputArea();
            return _uiOutputArea;
        }
        private function init():void
        {
            var label0:Label = new Label();
            label0.text = '输出信息';
            this.addElement(label0);
            
            infoTxt = new TextArea();
            infoTxt.y = 20;
            infoTxt.width = 420;
            infoTxt.height = 420;
            this.addElement(infoTxt);
        }
        /**
         * 输出信息
         */
        public function append( s:String ):void
        {
            infoTxt.text += s+'\n';
        }
    }
}