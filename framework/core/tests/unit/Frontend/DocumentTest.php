<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Frontend;

use Flarum\Frontend\Document;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DocumentTest extends TestCase
{
    #[Test]
    public function set_page_param_keeps_the_port_and_user_info()
    {
        $this->assertEquals(
            'http://user:pass@localhost:8080/d/1-example?sort=newest&page=3#post',
            Document::setPageParam('http://user:pass@localhost:8080/d/1-example?sort=newest&page=2#post', 3)
        );
    }

    #[Test]
    public function set_page_param_drops_the_question_mark_when_no_query_is_left()
    {
        $this->assertEquals(
            'http://localhost:8080/d/1-example',
            Document::setPageParam('http://localhost:8080/d/1-example?page=2', 1)
        );
    }
}
