<?php
declare(strict_types=1);

/**
 * Table-and-chairs drawing used on every room card. Occupied rooms show people in the chairs.
 */
function render_room_visual(array $room, string $state): void
{
    $seats = max(1, min(8, (int) $room['capacity']));
    $top = (int) ceil($seats / 2);
    $bottom = $seats - $top;
    $seat = $state === 'occupied' ? 'person' : 'chair';
    ?>
    <span class="room-visual" aria-hidden="true">
      <span class="seat-row">
<?php for ($i = 0; $i < $top; $i++): ?>
        <span class="<?= $seat ?>"></span>
<?php endfor; ?>
      </span>
      <span class="room-table">
        <span class="status-dot status-dot-<?= e($state) ?>"></span>
      </span>
      <span class="seat-row">
<?php for ($i = 0; $i < $bottom; $i++): ?>
        <span class="<?= $seat ?>"></span>
<?php endfor; ?>
      </span>
    </span>
    <?php
}

function render_facility_tags(string $facilities, string $class = 'tags'): void
{
    $list = facility_list($facilities);
    if (!$list) {
        echo '<p class="muted">No special facilities.</p>';
        return;
    }
    ?>
    <ul class="<?= e($class) ?>">
<?php foreach ($list as $item): ?>
      <li class="tag"><?= e($item) ?></li>
<?php endforeach; ?>
    </ul>
    <?php
}

function render_status_badge(string $kind, string $label): void
{
    echo '<span class="badge badge-' . e($kind) . '">' . e($label) . '</span>';
}

/** Open-desk strip for one floor. Available desks are selectable on the study-rooms plan. */
function render_quiet_hall(array $desks, bool $selectable = false): void
{
    if (!$desks) {
        return;
    }
    ?>
    <div class="quiet-hall">
      <div class="hall-desks">
<?php foreach ($desks as $desk):
    $status = (string) $desk['status'];
    $label = $desk['desk_code'] . ' · ' . quiet_desk_status_label($status);
    $class = 'hall-desk hall-desk-' . $status;
    if ($status === 'available') {
        $mark = '<span class="status-dot status-dot-available"></span>';
    } elseif ($status === 'occupied') {
        $mark = '<span class="person"></span>';
    } elseif ($status === 'out_of_order') {
        $mark = '<span class="status-dot status-dot-out_of_order"></span>';
    } else {
        $mark = '';
    }
    if ($selectable && $status === 'available'): ?>
        <button class="<?= e($class) ?>" type="button"
          data-desk-id="<?= (int) $desk['desk_id'] ?>"
          data-desk-code="<?= e($desk['desk_code']) ?>"
          data-floor-no="<?= (int) $desk['floor_no'] ?>"
          data-note="<?= e($desk['note']) ?>"
          data-status="available"
          title="<?= e($label) ?>"
          aria-label="<?= e($label) ?>"
          aria-pressed="false"><?= $mark ?></button>
<?php else: ?>
        <span class="<?= e($class) ?>" title="<?= e($label) ?>"><?= $mark ?></span>
<?php endif; ?>
<?php endforeach; ?>
      </div>
      <div class="hall-label">
        <span>Quiet hall &middot; open desks, walk-in</span>
        <span class="here-marker">You are here <span class="status-dot status-dot-here"></span></span>
      </div>
    </div>
    <?php
}
