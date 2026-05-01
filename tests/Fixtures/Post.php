<?php

namespace Madbox99\FilamentSeo\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Madbox99\FilamentSeo\Concerns\HasSeo;

class Post extends Model
{
    use HasSeo;

    protected $guarded = [];

    protected $table = 'posts';
}
