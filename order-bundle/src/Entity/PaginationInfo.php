<?php

namespace OrderBundle\Entity;

class PaginationInfo
{
    public function __construct(
        public readonly int $total,
        public readonly int $page,
        public readonly int $limit,
        public readonly int $pages
    ) {
    }

    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'page' => $this->page,
            'limit' => $this->limit,
            'pages' => $this->pages
        ];
    }
}
