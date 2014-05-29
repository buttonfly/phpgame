package controllers
{
    import mx.core.FlexGlobals;
    import view.UIConnect;
    import view.UIOutputArea;
    import view.UIParamArea;

    /**
     * 视图控制器
     * @author            Yanis
     * @version            1.0.0
     * @date            2011-12-7 下午02:56:34
     */
    public class ViewControl
    {
        public function ViewControl()
        {
            init();
        }
        
        private function init():void
        {
            //创建连接面板
            var uiConnect:UIConnect = new UIConnect();
            FlexGlobals.topLevelApplication.addElement(uiConnect);
            //创建参数区
            var uiParamArea:UIParamArea = new UIParamArea();
            uiParamArea.y = 30;
            FlexGlobals.topLevelApplication.addElement(uiParamArea);
            //创建输出信息区
            var uiOutputArea:UIOutputArea = UIOutputArea.getInstance();
            uiOutputArea.y = 10;
            uiOutputArea.x = 450;
            FlexGlobals.topLevelApplication.addElement(uiOutputArea);
        }
    }
}