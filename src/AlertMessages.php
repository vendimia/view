<?php
namespace Vendimia\View;

use Vendimia\Session\SessionManager;

/** 
 * 
 */
class AlertMessages
{

    public function __construct(
        private SessionManager $session,
    )
    {

    }

    public function addMessage(
        string $content,
        MessageType $type,
        string $extra = '',
        string $icon = '',
        array $options = []
    ) {
        $this->session['vendimia-alertmessages'][] = [
            'content' => $content,
            'type' => strtolower($type->name),
            'extra' => $extra,
            'icon' => $icon,
            'options' => $options,
        ];
    }

    public function retrieveMessages(): array
    {
        $messages = $this->session['vendimia-alertmessages'] ?? [];

        $this->session->remove('vendimia-alertmessages');

        return $messages;
    }

    /**
     * 
     */
    public function success(
        string $content, 
        string $extra = '',
        string $icon = '',
        array $options = []
    )
    {
        $this->addMessage($content, MessageType::SUCCESS, $extra, $icon, $options);
    }

    /**
     * 
     */
    public function info(
        string $content, 
        string $extra = '',
        string $icon = '',
        array $options = []
    )
    {
        $this->addMessage($content, MessageType::INFO, $extra, $icon, $options);
    }

    /**
     * 
     */
    public function warning(
        string $content, 
        string $extra = '',
        string $icon = '',
        array $options = []
    )
    {
        $this->addMessage($content, MessageType::WARNING, $extra, $icon, $options);
    }

    /**
     * 
     */
    public function error(
        string $content, 
        string $extra = '',
        string $icon = '',
        array $options = []
    )
    {
        $this->addMessage($content, MessageType::ERROR, $extra, $icon, $options);
    }
}