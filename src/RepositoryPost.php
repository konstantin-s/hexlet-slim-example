<?php


namespace App;


class RepositoryPost
{
    private $posts;

    public function __construct()
    {
        $this->posts = Generator::generatePosts(100);
    }

    public function all()
    {
        return $this->posts;
    }

    public function find(string $id)
    {
        return collect($this->posts)->firstWhere('id', $id);
    }
}