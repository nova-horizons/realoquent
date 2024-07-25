<?php

namespace Tests\Models;

enum UserTypeEnum: string
{
    case Admin = 'admin';
    case User = 'user';
}
