<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Models;

use Volunteersystem\Models\Faq;
use Volunteersystem\Models\Tag;

class FaqTest extends ModelTest
{
    /**
     * @covers \Volunteersystem\Models\Faq::tags
     */
    public function testFaqs(): void
    {
        /** @var Tag $tag1 */
        $tag1 = Tag::factory()->create();
        /** @var Tag $tag2 */
        $tag2 = Tag::factory()->create();

        $model = new Faq();
        $model->question = 'Some Question';
        $model->text = 'Some Answer';
        $model->save();

        $model->tags()->attach($tag1);
        $model->tags()->attach($tag2);

        /** @var Faq $savedModel */
        $savedModel = Faq::all()->last();
        $this->assertEquals($tag1->name, $savedModel->tags[0]->name);
        $this->assertEquals($tag2->name, $savedModel->tags[1]->name);
    }
}
