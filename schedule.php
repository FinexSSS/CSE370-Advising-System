<?php
// Weekly class schedule, shared by the student dashboard and both advising panels.
// Pages include this file and call render_schedule() where the table should go.

const SCHEDULE_DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// The regular class periods, always shown even when the student has no class in them.
const SCHEDULE_SLOTS = [
    ['8:00:00', '9:20:00'], ['9:30:00', '10:50:00'], ['11:00:00', '12:20:00'],
    ['12:30:00', '1:50:00'], ['2:00:00', '3:20:00'], ['3:30:00', '4:50:00'],
];

// Class times are stored as 12-hour clock text without AM or PM, e.g. "1:50:00".
// Classes start at 8 AM, so an hour below 8 is in the afternoon or evening.
function class_minutes($time) {
    [$hour, $minute] = array_map('intval', explode(':', $time));
    if ($hour < 8) {
        $hour += 12;
    }
    return $hour * 60 + $minute;
}

// Start and end of a class period in minutes after midnight.
function class_span($start, $end) {
    $from = class_minutes($start);
    $to = class_minutes($end);
    if ($to <= $from) {
        $to += 12 * 60; // e.g. the evening lab from 6:00 to 9:00
    }
    return [$from, $to];
}

// Two periods clash when they overlap at all.
function classes_clash(array $a, array $b) {
    return $a[0] < $b[1] && $b[0] < $a[1];
}

function format_minutes($minutes) {
    $hour = intdiv($minutes, 60) % 24;
    return sprintf('%d:%02d %s', ($hour + 11) % 12 + 1, $minutes % 60, $hour < 12 ? 'AM' : 'PM');
}

// The rooms and times of every class the student is enrolled in.
function student_classes($conn, $student_id) {
    $stmt = $conn->prepare("SELECT r.C_Code, r.Day, r.s_time, r.e_time, r.room_code FROM room r JOIN enrollment e ON e.C_Code = r.C_Code AND e.Section = r.Sections WHERE e.Student_ID = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function render_schedule($conn, $student_id) {
    $classes = student_classes($conn, $student_id);

    // One row per period: the regular ones plus any other period a class uses.
    $slots = [];
    foreach (array_merge(SCHEDULE_SLOTS, array_map(fn($c) => [$c['s_time'], $c['e_time']], $classes)) as [$start, $end]) {
        $slots["$start-$end"] = class_span($start, $end);
    }
    uasort($slots, fn($a, $b) => $a <=> $b);
    ?>
    <div class="schedule-table">
        <h2>Weekly Schedule</h2>
        <table>
            <tr>
                <th>Time / Day</th>
                <?php foreach (SCHEDULE_DAYS as $day): ?>
                    <th><?php echo $day; ?></th>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($slots as $key => $span): ?>
                <tr>
                    <td><?php echo format_minutes($span[0]) . ' - ' . format_minutes($span[1]); ?></td>
                    <?php foreach (SCHEDULE_DAYS as $day): ?>
                        <td>
                            <?php foreach ($classes as $class): ?>
                                <?php if ($class['Day'] === $day && $class['s_time'] . '-' . $class['e_time'] === $key): ?>
                                    <?php echo e($class['C_Code']); ?><br>
                                    Room: <?php echo e($class['room_code']); ?><br>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php
}
?>
