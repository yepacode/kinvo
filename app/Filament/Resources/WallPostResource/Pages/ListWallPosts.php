<?php

namespace App\Filament\Resources\WallPostResource\Pages;

use App\Filament\Resources\WallPostResource;
use Filament\Resources\Pages\ListRecords;

class ListWallPosts extends ListRecords
{
    protected static string $resource = WallPostResource::class;
}
