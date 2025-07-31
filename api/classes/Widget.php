<?php

define("HTML_TEMPLATE_PATH", __DIR__ . "/../../ui/templates/");
define("DEFAULT_WIDTH", 1200);
define("DEFAULT_HEIGHT", 800);
class ZCRMWidget
{

    function __construct() {}

    public function render($title, $css, $js, $body, $height = DEFAULT_HEIGHT, $width = DEFAULT_WIDTH)
    {

        $template_data = [
            "title" => $title,
            "css" => $css,
            "custom_js" => $js,
            "body" => $body,
            "width" => $width,
            "height" => $height,
        ];

        $html = $this->getWidgetHtml(HTML_TEMPLATE_PATH, $template_data);

        echo $html;
    }

    public function getWidgetHtml($template_path, $template_data)
    {
        $templates = new \League\Plates\Engine($template_path);
        $html = $templates->render("template", $template_data);
        return $html;
    }
}
