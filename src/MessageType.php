<?php
namespace Vendimia\View;


/**
 * Message type enum
 */
enum MessageType
{
    case SUCCESS;
    case INFO;
    case WARNING;
    case ERROR;
}