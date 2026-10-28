<?php

namespace Roddy\FirestoreEloquent\Tests\Unit;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use Roddy\FirestoreEloquent\Facade\FModel;

final class RelationshipsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container();
        $container->instance('config', new Repository([
            'firebase' => [
                'default' => 'app',
                'class_namespace' => __NAMESPACE__,
                'projects' => [
                    'app' => ['database' => ['project_id' => 'test-project']],
                ],
            ],
        ]));
        Container::setInstance($container);
    }

    public function test_models_include_relationship_helpers_with_conventional_keys(): void
    {
        $parent = new RelationshipParentFixture();
        $child = new RelationshipChildFixture();

        $hasMany = $parent->fHasMany(RelationshipChildFixture::class);
        $belongsTo = $child->fBelongsTo(RelationshipParentFixture::class);

        self::assertSame('relationship_parent_fixture_id', $hasMany['foreignKey']);
        self::assertSame('uuid', $hasMany['localKey']);
        self::assertSame('uuid', $belongsTo['foreignKey']);
        self::assertSame('relationship_parent_fixture_id', $belongsTo['localKey']);
    }

    public function test_short_related_names_use_the_configured_model_namespace(): void
    {
        $relation = (new RelationshipParentFixture())->fHasOne('RelationshipChildFixture');

        self::assertSame(RelationshipChildFixture::class, $relation['related']);
    }
}

final class RelationshipParentFixture extends FModel
{
    protected $primaryKey = 'uuid';
}

final class RelationshipChildFixture extends FModel
{
}
