<?php

namespace Quextum\EntityGrid;

interface IImageLinkProvider
{
    function provide($item, $width = null, $height = null, $flag = null);
}