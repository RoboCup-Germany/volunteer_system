<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Models\User;

use Volunteersystem\Models\BaseModel;
use Volunteersystem\Models\User\UsesUserModel;
use Volunteersystem\Test\Unit\Models\ModelTest;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsesUserModelTest extends ModelTest
{
    /**
     * @covers \Volunteersystem\Models\User\UsesUserModel::user
     */
    public function testHasOneRelations(): void
    {
        /** @var UsesUserModel $contact */
        $model = new class extends BaseModel
        {
            use UsesUserModel;
        };

        $this->assertInstanceOf(BelongsTo::class, $model->user());
    }
}
