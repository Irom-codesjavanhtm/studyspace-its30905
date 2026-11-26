<?php
declare(strict_types=1);

/** Floors students can open. Floor 1 is the entrance and is not part of the plan. */
function plan_floors(): array
{
    return [2, 3, 4, 5];
}

function plan_floor_closed(int $floor): bool
{
    return $floor === 3 || $floor === 5;
}

/**
 * Shared drawing for Level 2 and Level 4 on a 12 by 16 grid.
 * Column 3 and column 8 are open walkways, so the middle block sits centered.
 * Room slots 1–10 become R-201…R-210 or R-401…R-410.
 * The top-right block is the exit staircase, not a room.
 * Quiet halls are walk-in. The lift, stairs, shelves, WC and lounge are not bookable.
 *
 * @return array<int, array<string, mixed>>
 */
function plan_floor_blocks(int $floor): array
{
    $code = static function (int $slot) use ($floor): string {
        return sprintf('R-%d%02d', $floor, $slot);
    };

    return [
        ['type' => 'room', 'code' => $code(1), 'x' => 1, 'y' => 1, 'w' => 2, 'h' => 2],
        ['type' => 'room', 'code' => $code(2), 'x' => 1, 'y' => 3, 'w' => 2, 'h' => 2],
        ['type' => 'decor', 'kind' => 'lift', 'label' => 'Lift', 'x' => 1, 'y' => 5, 'w' => 2, 'h' => 1],
        ['type' => 'decor', 'kind' => 'bookshelf', 'label' => 'Bookshelves', 'x' => 1, 'y' => 6, 'w' => 2, 'h' => 7, 'vertical' => true],
        ['type' => 'decor', 'kind' => 'wc', 'label' => 'WC', 'x' => 1, 'y' => 13, 'w' => 2, 'h' => 2],
        ['type' => 'decor', 'kind' => 'bookshelf', 'label' => 'Bookshelves', 'x' => 1, 'y' => 15, 'w' => 5, 'h' => 2],
        ['type' => 'quiet', 'label' => 'Quiet hall', 'x' => 4, 'y' => 1, 'w' => 4, 'h' => 5],
        ['type' => 'room', 'code' => $code(3), 'x' => 4, 'y' => 6, 'w' => 2, 'h' => 2],
        ['type' => 'room', 'code' => $code(4), 'x' => 6, 'y' => 6, 'w' => 2, 'h' => 2],
        ['type' => 'room', 'code' => $code(5), 'x' => 4, 'y' => 8, 'w' => 2, 'h' => 2],
        ['type' => 'room', 'code' => $code(6), 'x' => 6, 'y' => 8, 'w' => 2, 'h' => 2],
        ['type' => 'quiet', 'label' => 'Quiet hall', 'x' => 4, 'y' => 10, 'w' => 4, 'h' => 1],
        ['type' => 'room', 'code' => $code(7), 'x' => 4, 'y' => 11, 'w' => 2, 'h' => 2],
        ['type' => 'room', 'code' => $code(8), 'x' => 6, 'y' => 11, 'w' => 2, 'h' => 2],
        ['type' => 'room', 'code' => $code(9), 'x' => 4, 'y' => 13, 'w' => 2, 'h' => 2],
        ['type' => 'room', 'code' => $code(10), 'x' => 6, 'y' => 13, 'w' => 2, 'h' => 2],
        ['type' => 'decor', 'kind' => 'stairs', 'label' => 'Exit', 'x' => 9, 'y' => 1, 'w' => 4, 'h' => 2],
        ['type' => 'quiet', 'label' => '', 'x' => 9, 'y' => 3, 'w' => 4, 'h' => 1],
        ['type' => 'decor', 'kind' => 'lounge', 'label' => '', 'x' => 9, 'y' => 4, 'w' => 4, 'h' => 6],
        ['type' => 'quiet', 'label' => 'Quiet hall', 'x' => 9, 'y' => 10, 'w' => 4, 'h' => 5],
        ['type' => 'quiet', 'label' => '', 'x' => 6, 'y' => 15, 'w' => 7, 'h' => 2],
    ];
}

function plan_place(array $block): string
{
    return sprintf(
        'grid-column:%d / span %d;grid-row:%d / span %d;',
        (int) $block['x'],
        (int) $block['w'],
        (int) $block['y'],
        (int) $block['h']
    );
}
