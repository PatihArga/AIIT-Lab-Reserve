// sampleData.jsx — lab loan booking types, date helpers, sample bookings.

// The four loan types ("notifications") shown on each card.
const LOAN_TYPES = {
  computer:       { label: "Computer",         badge: "Computer",  needsUnit: true,
                    fg: "#4f46e5", bg: "#eef0fe", bgDark: "rgba(99,102,241,.20)", fgDark: "#b4b8ff" },
  room_exclusive: { label: "Full Room",        badge: "Exclusive", needsUnit: false,
                    fg: "#0d9488", bg: "#e3f4f1", bgDark: "rgba(45,212,191,.18)", fgDark: "#7fe6d8" },
  room_sharing:   { label: "Full Room",        badge: "Sharing",   needsUnit: false,
                    fg: "#d97706", bg: "#fcf0dd", bgDark: "rgba(251,191,36,.18)", fgDark: "#fbd07f" },
  room_computer:  { label: "Room + Computer",  badge: "Room + PC", needsUnit: true,
                    fg: "#7c3aed", bg: "#f1ebfe", bgDark: "rgba(167,139,250,.20)", fgDark: "#cdb6ff" },
};

// AIIT computer units #1–9
const UNITS = Array.from({ length: 9 }, (_, i) => `AIIT#${i + 1}`);

// short label shown as the card title
function loanLabel(ev) {
  if (ev.type === "computer") return ev.unit || "Computer";
  if (ev.type === "room_computer") return ev.unit || "Room + PC";
  return LOAN_TYPES[ev.type].label; // "Full Room"
}

const DAY_NAMES = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
const MONTHS = ["January","February","March","April","May","June","July","August","September","October","November","December"];

// date utils ---------------------------------------------------
function startOfDay(d) { const x = new Date(d); x.setHours(0,0,0,0); return x; }
function addDays(d, n) { const x = new Date(d); x.setDate(x.getDate() + n); return x; }
function sameDay(a, b) { return a.getFullYear()===b.getFullYear() && a.getMonth()===b.getMonth() && a.getDate()===b.getDate(); }
function dateKey(d) { return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,"0")}-${String(d.getDate()).padStart(2,"0")}`; }

function daysForView(anchor, view, weekStart, showWeekends) {
  if (view === "day") return [startOfDay(anchor)];
  if (view === "3day") return [0,1,2].map(i => addDays(startOfDay(anchor), i));
  const a = startOfDay(anchor);
  const dow = a.getDay();
  let offset = (dow - weekStart + 7) % 7;
  const weekStartDate = addDays(a, -offset);
  let days = [0,1,2,3,4,5,6].map(i => addDays(weekStartDate, i));
  if (view === "work") {
    days = days.filter(d => d.getDay() !== 0 && d.getDay() !== 6);
  } else if (!showWeekends) {
    days = days.filter(d => d.getDay() !== 0 && d.getDay() !== 6);
  }
  return days;
}

function fmtTime(mins, h24) {
  let h = Math.floor(mins / 60);
  const m = mins % 60;
  if (h24) return `${String(h).padStart(2,"0")}:${String(m).padStart(2,"0")}`;
  const ap = h < 12 ? "AM" : "PM";
  let hh = h % 12; if (hh === 0) hh = 12;
  return m === 0 ? `${hh} ${ap}` : `${hh}:${String(m).padStart(2,"0")} ${ap}`;
}

// sample bookings anchored to the current real week ------------
function buildSampleEvents() {
  const today = startOfDay(new Date());
  const dow = today.getDay();
  const monday = addDays(today, -((dow + 6) % 7));
  let id = 1;
  const T = (h, m=0) => h*60 + m;
  // (dayIdx, start, dur, type, who, unit)
  const ev = (dayIdx, start, dur, type, who, unit) => ({
    id: id++, date: dateKey(addDays(monday, dayIdx)), start, dur, type, who, unit,
  });
  return [
    // Monday
    ev(0, T(9),    90,  "computer",       "Andi P.",      "AIIT#3"),
    ev(0, T(9),    120, "room_exclusive", "Robotics Lab", null),
    ev(0, T(13),   60,  "computer",       "Sari W.",      "AIIT#7"),
    ev(0, T(14,30),90,  "room_sharing",   "Study Group A", null),
    // Tuesday
    ev(1, T(8,30), 120, "room_computer",  "AI Workshop",  "AIIT#1–4"),
    ev(1, T(11),   60,  "computer",       "Budi S.",      "AIIT#2"),
    ev(1, T(13),   90,  "room_sharing",   "Open Lab",     null),
    ev(1, T(15,30),60,  "computer",       "Maya L.",      "AIIT#5"),
    // Wednesday
    ev(2, T(9),    180, "room_exclusive", "Thesis Defense", null),
    ev(2, T(13),   60,  "computer",       "Rian A.",      "AIIT#9"),
    ev(2, T(14,30),90,  "computer",       "Dina K.",      "AIIT#4"),
    // Thursday — concurrent computer rush (demonstrates split + roll-up)
    ev(3, T(10),   60,  "computer",       "Eko P.",       "AIIT#1"),
    ev(3, T(10),   60,  "computer",       "Nadia R.",     "AIIT#2"),
    ev(3, T(10),   60,  "computer",       "Galih W.",     "AIIT#3"),
    ev(3, T(10),   60,  "computer",       "Putri A.",     "AIIT#4"),
    ev(3, T(10),   60,  "computer",       "Rizki H.",     "AIIT#5"),
    ev(3, T(8,30), 90,  "room_computer",  "ML Bootcamp",  "AIIT#1–9"),
    ev(3, T(11),   60,  "computer",       "Tono H.",      "AIIT#6"),
    ev(3, T(13,30),120, "room_sharing",   "Open Lab",     null),
    ev(3, T(16),   45,  "computer",       "Fitri N.",     "AIIT#8"),
    // Friday
    ev(4, T(9),    60,  "computer",       "Eka R.",       "AIIT#1"),
    ev(4, T(10,30),120, "room_exclusive", "Faculty Demo", null),
    ev(4, T(13,30),90,  "room_computer",  "Hackathon",    "AIIT#5–9"),
    ev(4, T(15,30),60,  "computer",       "Yusuf M.",     "AIIT#3"),
    // weekend
    ev(5, T(10),   120, "room_sharing",   "Weekend Lab",  null),
    ev(6, T(13),   90,  "computer",       "Lina T.",      "AIIT#2"),
  ];
}

Object.assign(window, {
  LOAN_TYPES, UNITS, loanLabel, DAY_NAMES, MONTHS,
  startOfDay, addDays, sameDay, dateKey, daysForView, fmtTime, buildSampleEvents,
});
