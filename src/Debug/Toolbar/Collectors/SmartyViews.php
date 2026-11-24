<?php

namespace CI4Smarty\Debug\Toolbar\Collectors;

use CI4Smarty\Config\Services;
use CodeIgniter\Debug\Toolbar\Collectors\BaseCollector;

final class SmartyViews extends BaseCollector
{
    protected $hasTimeline = true;

    protected $title = 'Smarty Views';

    protected function formatTimelineData(): array
    {
        return Services::smartyRenderer()->getTimelineData();
    }
}
