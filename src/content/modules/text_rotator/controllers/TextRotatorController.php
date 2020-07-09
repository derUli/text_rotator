<?php

use UliCMS\HTML\ListItem;

class TextRotatorController extends MainClass
{
    const MODULE_NAME = "text_rotator";

    public function getSettingsHeadline()
    {
        return get_translation("text_rotator");
    }

    public function settings()
    {
        return Template::executeModuleTemplate(
            self::MODULE_NAME,
            "list.php"
        );
    }

    public function adminHead()
    {
        enqueueStylesheet(
            ModuleHelper::buildRessourcePath(self::MODULE_NAME, "node_modules/animate.css/animate.min.css")
        );
        enqueueStylesheet(
            ModuleHelper::buildRessourcePath(self::MODULE_NAME, "node_modules/morphext/dist/morphext.css")
        );
        combinedStylesheetHtml();
    }

    private function currentPageContainsRotatingText()
    {
        $page = ContentFactory::getCurrentPage();
        return str_contains($page->content, "[rotating_text=");
    }

    public function enqueueFrontendStylesheets()
    {
        if ($this->currentPageContainsRotatingText()) {
            enqueueStylesheet(
                ModuleHelper::buildRessourcePath(self::MODULE_NAME, "node_modules/animate.css/animate.min.css")
            );
            enqueueStylesheet(
                ModuleHelper::buildRessourcePath(self::MODULE_NAME, "node_modules/morphext/dist/morphext.css")
            );
        }
    }

    public function enqueueFrontendFooterScripts()
    {
        if ($this->currentPageContainsRotatingText()) {
            enqueueScriptFile(
                ModuleHelper::buildRessourcePath(self::MODULE_NAME, "node_modules/morphext/dist/morphext.min.js")
            );
            enqueueScriptFile(
                ModuleHelper::buildRessourcePath(self::MODULE_NAME, "js/text_rotator.js")
            );
        }
    }

    public function preview()
    {
        $words = Request::getVar("words", "", "str");
        $separator = Request::getVar("separator", ",", "str");
        $speed = Request::getVar("speed", 2000, "int");
        $animation = Request::getVar("animation", "", "str");

        $preview = $this->_preview($words, $separator, $speed, $animation);
        HtmlResult($preview);
    }

    public function _preview(
        string $words,
        string $separator,
        int $speed,
        string $animation
    ) {
        $rotating_text = new RotatingText();
        $words = Request::getVar("words", "", "str");
        $separator = Request::getVar("separator", ",", "str");
        $speed = Request::getVar("speed", 2000, "int");
        $animation = Request::getVar("animation", "", "str");

        $rotating_text->setWords($words);
        $rotating_text->setSeparator($separator);
        $rotating_text->setSpeed($speed);
        $rotating_text->setAnimation($animation);

        return $rotating_text->getHtml();
    }

    public function beforeContentFilter($html)
    {
        $texts = RotatingText::getAll();
        foreach ($texts as $text) {
            if (str_contains($html, $text->getShortcode())) {
                $html = str_replace($text->getShortcode(), $text->getHtml(), $html);
            }
        }
        return $html;
    }

    public function savePost()
    {
        $id = Request::getVar("id", null, "int");
        $words = Request::getVar("words", "", "str");
        $separator = Request::getVar("separator", ",", "str");
        $speed = Request::getVar("speed", 2000, "int");
        $animation = Request::getVar("animation", "", "str");

        $success = $this->_savePost(
            $words,
            $separator,
            $speed,
            $animation,
            $id
        );

        Response::redirect(
            ModuleHelper::buildAdminURL(
                    self::MODULE_NAME
                )
        );
    }

    public function _savePost(
        string $words,
        string $separator,
        int $speed,
        string $animation,
        ?int $id
    ) {
        $rotating_text = new RotatingText();
        if ($id) {
            $rotating_text->loadByID($id);
        }

        $rotating_text->setWords($words);
        $rotating_text->setSeparator($separator);
        $rotating_text->setSpeed($speed);
        $rotating_text->setAnimation($animation);

        $rotating_text->save();
        return $rotating_text->isPersistent() && !$rotating_text->hasChanges();
    }

    public function getAnimationItems()
    {
        $fx = [
            "attention_seekers" => [
                "bounce",
                "flash",
                "pulse",
                "rubberBand",
                "shake",
                "swing",
                "tada",
                "wobble"
            ],
            "bouncing_entrances" => [
                "bounceIn",
                "bounceInDown",
                "bounceInLeft",
                "bounceInRight",
                "bounceInUp"
            ],
            "fading_entrances" => [
                "fadeIn",
                "fadeInDown",
                "fadeInDownBig",
                "fadeInLeft",
                "fadeInLeftBig",
                "fadeInRight",
                "fadeInRightBig",
                "fadeInUp",
                "fadeInUpBig"
            ],
            "flipping_entrances" => [
                "flip",
                "flipInX",
                "flipInY"
            ],
            "rotating_entrances" => [
                "rotateIn",
                "rotateInDownLeft",
                "rotateInDownRight",
                "rotateInUpLeft",
                "rotateInUpRight"
            ],
            "zoom_entrances" => [
                "zoomIn",
                "zoomInDown",
                "zoomInLeft",
                "zoomInRight",
                "zoomInUp"
            ],
            "others" => [
                "lightSpeedIn",
                "rollIn"
            ]
        ];
        $items = [];
        foreach ($fx as $type => $effects) {
            foreach ($effects as $effect) {
                $translatedType = get_translation("fx_type_{$type}");
                $item = new ListItem(
                    $effect,
                    "$effect ({$translatedType})"
                );
                $items[] = $item;
            }
        }
        return $items;
    }

    public function deletePost()
    {
        $id = Request::getVar("id", 0, "int");

        $success = $this->_deletePost($id);

        if (!$success) {
            ExceptionResult(get_translation("not_found"), HttpStatusCode::NOT_FOUND);
        }

        Response::sendHttpStatusCodeResultIfAjax(
            HttpStatusCode::OK,
            ModuleHelper::buildAdminUrl(self::MODULE_NAME)
        );
    }

    public function _deletePost(int $id)
    {
        $textRotator = new RotatingText($id);

        $success = false;

        if ($textRotator->isPersistent()) {
            $textRotator->delete();
            $success = !$textRotator->isPersistent();
        }

        return $success;
    }
}
