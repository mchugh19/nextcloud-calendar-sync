<?php

namespace OCA\CalDavSync\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Controller;
use OCP\Util;
use OCP\IUserSession;
use OCA\DAV\CalDAV\CalDavBackend;

class PageController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private IUserSession $userSession,
        private CalDavBackend $calDavBackend
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse {
        Util::addScript('calendar-sync', 'lib/jquery.min');
        Util::addScript('calendar-sync', 'calendar-sync-main');
        Util::addStyle('calendar-sync', 'style');

        // Fetch user's calendars server-side (avoids API route issues)
        $calendars = [];
        $user = $this->userSession->getUser();
        if ($user !== null) {
            try {
                $raw = $this->calDavBackend->getCalendarsForUser('principals/users/' . $user->getUID());
                foreach ($raw as $cal) {
                    $calendars[] = [
                        'uri' => $cal['uri'] ?? '',
                        'name' => $cal['{DAV:}displayname'] ?? $cal['uri'] ?? '',
                    ];
                }
            } catch (\Exception $e) {
                // leave empty, template will show fallback
            }
        }

        return new TemplateResponse('calendar-sync', 'main', ['calendars' => $calendars]);
    }
}