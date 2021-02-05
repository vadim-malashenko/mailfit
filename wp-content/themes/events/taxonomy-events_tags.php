<?php get_header();

$monthsPerPage = 5;
$postsPerSlide = 4;
$queriedTerm = \get_queried_object();

$query = new \WP_Query(
    [
        'post_type' => 'events',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_key' => 'start',
        'orderby' => 'meta_value',
        'meta_type' => 'DATE',
        'order' => 'ASC',
        'tax_query' => [[
            'taxonomy' => "events_tags",
            'field' => 'slug',
            'terms' => [$queriedTerm->slug]
        ]]
    ]
);

$timestamps = array_filter(
    array_map(
        static fn(\WP_Post $post): int => strtotime(\get_field('end', $post->ID)),
        $query->posts
    ),
    static fn(int $timestamp): bool => $timestamp >= time()
);

sort($timestamps);

$months = array_slice(
    array_unique(
        array_map(
            static fn(int $timestamp): string => date('F Y', $timestamp),
            $timestamps
        )
    ),
    0,
    $monthsPerPage
);

$terms = get_terms([
    'taxonomy' => 'events_tags',
    'hide_empty' => false
]);

$tags = array_reduce(
    $terms,
    static function (array $terms, \WP_Term $term): array {
        if (isset($terms[$term->parent])) {
            $terms[$term->parent]->count += $term->count;
        }
        return $terms;
    },
    array_filter(
        $terms,
        static fn(\WP_Term $term): bool => $term->parent === 0
    )
);

$posts = array_values(
    array_filter(
        $query->posts,
        static fn(\WP_Post $post): bool => strtotime(\get_field('end', $post->ID)) > time() && strtotime(\get_field('start', $post->ID)) <= strtotime(date('Y-m-t', strtotime(end($months))))
    )
);

$postsCount = count($posts); ?>

<div class="mt-4 d-flex justify-content-center">
    <?php foreach ($tags as $i => $tag) : ?>
        <div class="mx-2">
            <a href="/events_tags/<?php echo $tag->slug; ?>" class="btn btn-<?php echo $queriedTerm->term_id === $tag->term_id ? 'primary' : 'secondary'; ?> btn-sm rounded-0">
                <?php echo $tag->name; ?>
                <span class="badge bg-secondary"><?php echo $tag->count; ?></span>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="mt-4 d-flex justify-content-center">
    <?php foreach ($months as $i => $month) : ?>
        <div class="mx-2 <?php echo $i ? 'text-muted' : 'fw-bold'; ?>">
            <?php echo $month; ?>
        </div>
    <?php endforeach; ?>
</div>

<div id="carouselExampleControls" class="mt-5 carousel carousel-fade carousel-dark" data-interval="false">
    <div class="carousel-inner">
        <?php for ($i = 0, $l = $postsCount / $postsPerSlide; $i < $l; $i++) : ?>
            <div class="d-flex justify-content-center carousel-item <?php echo $i ? '' : 'active'; ?>">
                <?php for ($j = 0; $j < $postsPerSlide; $j++) : ?>
                    <?php if ( ! isset($posts[$k = $postsPerSlide * $i + $j])) break; ?>
                    <?php $post = $posts[$k]; ?>
                    <ul style="list-group">
                        <li class="list-group-item">Title: <?php the_title(); ?></li>
                        <li class="list-group-item">Start: <?php echo get_field('start', $post); ?></li>
                        <li class="list-group-item">End: <?php echo get_field('end', $post); ?></li>
                        <li class="list-group-item">Tags: <?php echo implode(', ', array_map(static fn (\WP_Term $term): string => $term->name, get_the_terms($post, 'events_tags'))); ?></li>
                    </ul>
                <?php endfor; ?>
            </div>
        <?php endfor; ?>
    </div>
    <?php if ($postsCount > $postsPerSlide) : ?>
        <a class="carousel-control-prev" href="#carouselExampleControls" role="button" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </a>
        <a class="carousel-control-next" href="#carouselExampleControls" role="button" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </a>
    <?php endif; ?>
</div>

<?php get_footer();