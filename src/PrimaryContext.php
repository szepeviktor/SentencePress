<?php

/**
 * Primary WordPress execution contexts.
 *
 * @author  Viktor Szépe <viktor@szepe.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/szepeviktor/SentencePress
 */

declare(strict_types=1);

namespace SzepeViktor\SentencePress;

/**
 * Mutually exclusive WordPress entry points.
 */
enum PrimaryContext: string
{
    case Installing = 'installing';
    case WpCli = 'wp-cli';
    case Cli = 'cli';
    case Cron = 'cron';
    case Ajax = 'ajax';
    case Rest = 'rest';
    case XmlRpc = 'xmlrpc';
    case Login = 'login';
    case AsyncUpload = 'async-upload';
    case AdminPost = 'admin-post';
    case Admin = 'admin';
    /** Frontend. */
    case PublicWeb = 'public-web';
}
