// App.jsx — Scheduler calendar
const { useState, useRef, useEffect, useCallback } = React;

const ACCENTS = {
  Indigo: "#4f46e5", Teal: "#0d9488", Orange: "#ea580c", Rose: "#db2777", Blue: "#2563eb",
};
const RANGES = {
  "Business (8–6)": [8, 18],
  "Extended (6–10)": [6, 22],
  "Full 24h": [0, 24],
};

// ---- tiny inline icons ----
const Ico = {
  chevL: <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="15 18 9 12 15 6"/></svg>,
  chevR: <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="9 18 15 12 9 6"/></svg>,
  plus: <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>,
  clock: <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>,
  cal: <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="16" y1="2" x2="16" y2="6"/></svg>,
  tag: <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M20.59 13.41 13.42 20.6a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z"/><circle cx="7" cy="7" r="1.2" fill="currentColor"/></svg>,
  user: <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>,
};

function snap(mins, step) { return Math.round(mins / step) * step; }

// ===== Concurrent-booking layout (Solution B: side-by-side splitting) =====
// Bookings that share a time slot are packed into columns and split the
// available width, like Google/Apple Calendar. When a slot gets denser than
// MAX_COLS, the surplus collapses into one "+N more" roll-up tile.
const MAX_COLS = 4;

function layoutDay(evs) {
  // sort by start, then longest first for stable packing
  const sorted = [...evs].sort((a, b) => a.start - b.start || (b.dur - a.dur) || a.id - b.id);

  // group into clusters of transitively-overlapping bookings
  const clusters = [];
  let cur = [], curEnd = -1;
  for (const e of sorted) {
    if (cur.length && e.start >= curEnd) { clusters.push(cur); cur = []; curEnd = -1; }
    cur.push(e);
    curEnd = Math.max(curEnd, e.start + e.dur);
  }
  if (cur.length) clusters.push(cur);

  const items = [];
  for (const cluster of clusters) {
    // greedy column assignment
    const colEnds = [];
    for (const e of cluster) {
      let placed = false;
      for (let i = 0; i < colEnds.length; i++) {
        if (colEnds[i] <= e.start) { colEnds[i] = e.start + e.dur; e._col = i; placed = true; break; }
      }
      if (!placed) { e._col = colEnds.length; colEnds.push(e.start + e.dur); }
    }
    const ncols = colEnds.length;

    if (ncols <= MAX_COLS) {
      const w = 1 / ncols;
      for (const e of cluster) items.push({ kind: "event", ev: e, left: e._col * w, width: w });
    } else {
      // keep the first (MAX_COLS - 1) columns, roll up the remainder
      const keep = MAX_COLS - 1;
      const w = 1 / MAX_COLS;
      const bucket = [];
      for (const e of cluster) {
        if (e._col < keep) items.push({ kind: "event", ev: e, left: e._col * w, width: w });
        else bucket.push(e);
      }
      if (bucket.length) {
        const start = Math.min(...bucket.map((e) => e.start));
        const end = Math.max(...bucket.map((e) => e.start + e.dur));
        items.push({ kind: "rollup", items: bucket, start, dur: end - start, left: keep * w, width: w });
      }
    }
  }
  return items;
}

// inline left/width (fractions 0–1) → calc() with a hairline gutter
function frameStyle(left, width, top, height) {
  return {
    top, height: Math.max(height - 2, 16),
    left: `calc(${(left * 100).toFixed(4)}% + 3px)`,
    width: `calc(${(width * 100).toFixed(4)}% - 4px)`,
  };
}

// ===== Booking card =====
function EventCard({ ev, dark, hourH, dayStartMin, onSelect, selected, h24, left, width }) {
  const tt = LOAN_TYPES[ev.type];
  const top = ((ev.start - dayStartMin) / 60) * hourH;
  const height = (ev.dur / 60) * hourH;
  const compact = height < 42;
  const narrow = width < 0.34; // split thin → drop the badge, keep title
  const style = {
    ...frameStyle(left, width, top, height),
    "--ev": tt.fg,
    "--ev-bg": dark ? tt.bgDark : tt.bg,
    "--ev-fg": dark ? tt.fgDark : tt.fg,
  };
  return (
    <div
      className={`event${compact ? " compact" : ""}${narrow ? " narrow" : ""}${selected ? " selected" : ""}`}
      style={style}
      onMouseDown={(e) => e.stopPropagation()}
      onClick={(e) => { e.stopPropagation(); onSelect(ev, e.currentTarget); }}
    >
      <div className="ev-row">
        <span className="ev-badge" style={{ background: tt.fg }}>{tt.badge}</span>
        <span className="ev-title">{loanLabel(ev)}</span>
      </div>
      <div className="ev-sub">{ev.who}</div>
      <div className="ev-time">{fmtTime(ev.start, h24)} – {fmtTime(ev.start + ev.dur, h24)}</div>
    </div>
  );
}

// ===== Roll-up tile for dense concurrent slots =====
function RollupCard({ item, dark, hourH, dayStartMin, h24, onOpen }) {
  const top = ((item.start - dayStartMin) / 60) * hourH;
  const height = (item.dur / 60) * hourH;
  const compact = height < 42;
  const narrow = item.width < 0.34;
  const n = item.items.length;
  const allComputers = item.items.every((e) => e.type === "computer");
  const label = allComputers ? `+${n} computers` : `+${n} bookings`;
  return (
    <div
      className={`event rollup${compact ? " compact" : ""}${narrow ? " narrow" : ""}`}
      style={frameStyle(item.left, item.width, top, height)}
      onMouseDown={(e) => e.stopPropagation()}
      onClick={(e) => { e.stopPropagation(); onOpen(item, e.currentTarget); }}
      title={`${label} — ${fmtTime(item.start, h24)}–${fmtTime(item.start + item.dur, h24)}`}
    >
      <div className="rollup-count">+{n}</div>
      <div className="ev-title">{narrow ? "more" : label}</div>
      <div className="ev-time">{fmtTime(item.start, h24)} – {fmtTime(item.start + item.dur, h24)}</div>
    </div>
  );
}

// ===== Create / edit booking card =====
const LOAN_OPTS = [
  { key: "computer",      name: "Computer only",  badge: "Computer",  fg: "#4f46e5", needsUnit: true },
  { key: "room_computer", name: "Room + Computer", badge: "Room + PC", fg: "#7c3aed", needsUnit: true },
  { key: "room_only",     name: "Room only",       badge: "Room",      fg: "#0d9488", needsUnit: false },
];

function CreatePopover({ draft, anchorRect, onSave, onCancel, h24 }) {
  // derive UI loan-type + room mode from the stored event type
  const initLoan = draft.type === "room_computer" ? "room_computer"
    : (draft.type === "room_exclusive" || draft.type === "room_sharing") ? "room_only" : "computer";
  const [loanType, setLoanType] = useState(initLoan);
  const [roomMode, setRoomMode] = useState(draft.type === "room_exclusive" ? "exclusive" : "sharing");
  const [unit, setUnit] = useState(draft.unit || "AIIT#1");
  const [start, setStart] = useState(draft.start);
  const [end, setEnd] = useState(draft.start + draft.dur);
  const [reason, setReason] = useState(draft.who && draft.who !== "Reserved" ? draft.who : "");
  const inputRef = useRef(null);
  useEffect(() => { inputRef.current && inputRef.current.focus(); }, []);

  const opt = LOAN_OPTS.find((o) => o.key === loanType);
  const pos = placePopover(anchorRect, 470);

  // 30-min time slots
  const slots = [];
  for (let m = 0; m <= 24 * 60; m += 30) slots.push(m);
  const startSlots = slots.filter((m) => m < 24 * 60);
  const endSlots = slots.filter((m) => m > start);

  const d = new Date(draft.date + "T00:00:00");
  const dateLabel = `${DAY_NAMES[d.getDay()]}, ${MONTHS[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`;

  const onStartChange = (v) => {
    setStart(v);
    if (end <= v) setEnd(Math.min(v + 30, 24 * 60));
  };

  const commit = () => {
    let type, u = null;
    if (loanType === "computer") { type = "computer"; u = unit; }
    else if (loanType === "room_computer") { type = "room_computer"; u = unit; }
    else { type = roomMode === "exclusive" ? "room_exclusive" : "room_sharing"; }
    const safeEnd = end > start ? end : Math.min(start + 30, 24 * 60);
    onSave({ ...draft, type, unit: u, start, dur: safeEnd - start, who: reason || "Reserved" });
  };

  return (
    <div className="pop-overlay" onMouseDown={onCancel}>
      <div className="pop wide" style={pos} onMouseDown={(e) => e.stopPropagation()}>
        <h3>{draft.id ? "Edit booking" : "Block time · New booking"}</h3>

        <label>Date</label>
        <div className="date-field">{Ico.cal}<span>{dateLabel}</span></div>

        <div className="field-row">
          <div>
            <label>Start time</label>
            <select value={start} onChange={(e) => onStartChange(+e.target.value)}>
              {startSlots.map((m) => <option key={m} value={m}>{fmtTime(m, h24)}</option>)}
            </select>
          </div>
          <div>
            <label>End time</label>
            <select value={end} onChange={(e) => setEnd(+e.target.value)}>
              {endSlots.map((m) => <option key={m} value={m}>{fmtTime(m, h24)}</option>)}
            </select>
          </div>
        </div>

        <label>Loan type</label>
        <div className="type-list">
          {LOAN_OPTS.map((o) => (
            <button key={o.key} className={`type-opt${loanType === o.key ? " on" : ""}`} onClick={() => setLoanType(o.key)}
              style={loanType === o.key ? { borderColor: o.fg, background: `color-mix(in oklch, ${o.fg} 8%, transparent)` } : null}>
              <span className="type-swatch" style={{ background: o.fg }} />
              <span className="type-name">{o.name}</span>
              <span className="type-badge" style={{ background: o.fg }}>{o.badge}</span>
            </button>
          ))}
        </div>

        {loanType === "room_only" && (
          <div className="submode">
            {["sharing", "exclusive"].map((m) => (
              <button key={m} className={roomMode === m ? "on" : ""} onClick={() => setRoomMode(m)}>
                {m === "sharing" ? "Sharing" : "Exclusive"}
              </button>
            ))}
          </div>
        )}

        {opt.needsUnit && (
          <React.Fragment>
            <label>Computer unit</label>
            <select value={unit} onChange={(e) => setUnit(e.target.value)}>
              {(UNITS.includes(unit) ? UNITS : [unit, ...UNITS]).map((u) => <option key={u} value={u}>{u}</option>)}
            </select>
          </React.Fragment>
        )}

        <label>Reason</label>
        <input ref={inputRef} type="text" value={reason} placeholder="Purpose of the loan…"
          onChange={(e) => setReason(e.target.value)}
          onKeyDown={(e) => { if (e.key === "Enter") commit(); }} />

        <div className="pop-actions">
          <button className="btn" onClick={onCancel}>Cancel</button>
          <button className="btn primary" onClick={commit}>{draft.id ? "Save" : "Confirm booking"}</button>
        </div>
      </div>
    </div>
  );
}

// ===== Details popover =====
function DetailsPopover({ ev, anchorRect, dark, onClose, onDelete, onEdit, h24 }) {
  const tt = LOAN_TYPES[ev.type];
  const pos = placePopover(anchorRect, 250);
  const d = new Date(ev.date + "T00:00:00");
  const dayLabel = `${DAY_NAMES[d.getDay()]}, ${MONTHS[d.getMonth()].slice(0,3)} ${d.getDate()}`;
  return (
    <div className="pop-overlay" onMouseDown={onClose}>
      <div className="pop" style={pos} onMouseDown={(e) => e.stopPropagation()}>
        <div className="det-head">
          <span className="det-swatch" style={{ background: tt.fg }} />
          <div>
            <div className="det-title">{loanLabel(ev)}</div>
            <div className="det-cat-tag" style={{ display: "inline-block", marginTop: 5, background: dark ? tt.bgDark : tt.bg, color: dark ? tt.fgDark : tt.fg }}>{tt.label} · {tt.badge}</div>
          </div>
        </div>
        <div className="det-row">{Ico.user}<span>{ev.who}</span></div>
        <div className="det-row">{Ico.clock}<span>{fmtTime(ev.start, h24)} – {fmtTime(ev.start + ev.dur, h24)}</span></div>
        <div className="det-row">{Ico.cal}<span>{dayLabel}</span></div>
        <div className="pop-actions" style={{ marginTop: 14 }}>
          <button className="btn danger" onClick={() => onDelete(ev.id)}>Cancel booking</button>
          <button className="btn" onClick={onClose}>Close</button>
          <button className="btn primary" onClick={() => onEdit(ev)}>Edit</button>
        </div>
      </div>
    </div>
  );
}

// ===== Group popover — lists every booking inside a rolled-up slot =====
function GroupPopover({ group, anchorRect, dark, h24, onClose, onDelete, onOpenOne }) {
  const pos = placePopover(anchorRect, 360);
  const sorted = [...group.items].sort((a, b) => a.start - b.start || a.id - b.id);
  return (
    <div className="pop-overlay" onMouseDown={onClose}>
      <div className="pop" style={pos} onMouseDown={(e) => e.stopPropagation()}>
        <h3 style={{ marginBottom: 4 }}>{group.items.length} bookings this slot</h3>
        <div className="group-sub">{fmtTime(group.start, h24)} – {fmtTime(group.start + group.dur, h24)}</div>
        <div className="group-list">
          {sorted.map((ev) => {
            const tt = LOAN_TYPES[ev.type];
            return (
              <div key={ev.id} className="group-row" onClick={(e) => onOpenOne(ev, e.currentTarget)}>
                <span className="det-swatch" style={{ background: tt.fg, marginTop: 3 }} />
                <div className="group-row-main">
                  <div className="group-row-title">{loanLabel(ev)}</div>
                  <div className="group-row-meta">{ev.who} · {fmtTime(ev.start, h24)}–{fmtTime(ev.start + ev.dur, h24)}</div>
                </div>
                <button className="group-cancel" title="Cancel booking"
                  onClick={(e) => { e.stopPropagation(); onDelete(ev.id); }}>×</button>
              </div>
            );
          })}
        </div>
        <div className="pop-actions" style={{ marginTop: 12 }}>
          <button className="btn" onClick={onClose}>Close</button>
        </div>
      </div>
    </div>
  );
}

// place a popover near an anchor rect, clamped to viewport
function placePopover(rect, h) {
  const w = 320, pad = 12;
  let left = rect.right + 10;
  if (left + w > window.innerWidth - pad) left = rect.left - w - 10;
  if (left < pad) left = pad;
  let top = rect.top;
  if (top + h > window.innerHeight - pad) top = window.innerHeight - h - pad;
  if (top < pad) top = pad;
  return { left, top };
}

// ===== Main App =====
function App() {
  const [t, setTweak] = useTweaks(TWEAK_DEFAULTS);
  const [events, setEvents] = useState(() => buildSampleEvents());
  const [anchor, setAnchor] = useState(() => startOfDay(new Date()));
  const [view, setView] = useState("work");
  const [creating, setCreating] = useState(null); // {draft, rect}
  const [details, setDetails] = useState(null);    // {ev, rect}
  const [group, setGroup] = useState(null);         // {items, start, dur, rect}
  const [block, setBlock] = useState(null); // drag-to-block selection
  const blockRef = useRef(null);
  const [now, setNow] = useState(() => new Date());
  const canvasRef = useRef(null);
  const colRefs = useRef({});

  const accent = ACCENTS[t.accent] || ACCENTS.Indigo;
  const dark = t.theme === "Dark";
  const h24 = t.timeFormat === "24-hour";
  const [rangeStart, rangeEnd] = RANGES[t.timeRange] || RANGES["Extended (6–10)"];
  const dayStartMin = rangeStart * 60;
  const hourH = { Compact: 46, Regular: 60, Comfortable: 76 }[t.density] || 60;
  const weekStart = t.weekStart === "Sunday" ? 0 : 1;
  const totalHours = rangeEnd - rangeStart;
  const canvasH = totalHours * hourH;

  const days = daysForView(anchor, view, weekStart, t.showWeekends);
  const ndays = days.length;

  // tick "now" every minute
  useEffect(() => {
    const id = setInterval(() => setNow(new Date()), 30000);
    return () => clearInterval(id);
  }, []);

  // ----- header period label -----
  const first = days[0], last = days[days.length - 1];
  let periodLabel, periodSub;
  if (view === "day") {
    periodLabel = `${MONTHS[first.getMonth()]} ${first.getDate()}`;
    periodSub = first.getFullYear();
  } else if (first.getMonth() === last.getMonth()) {
    periodLabel = MONTHS[first.getMonth()];
    periodSub = first.getFullYear();
  } else {
    periodLabel = `${MONTHS[first.getMonth()].slice(0,3)} – ${MONTHS[last.getMonth()].slice(0,3)}`;
    periodSub = first.getFullYear();
  }

  // ----- navigation -----
  const step = view === "day" ? 1 : view === "3day" ? 3 : 7;
  const go = (dir) => setAnchor((a) => addDays(a, dir * step));
  const goToday = () => setAnchor(startOfDay(new Date()));

  // ----- drag to block a time range on the hour column -----
  const onBlockStart = (e, day) => {
    if (e.button !== 0) return;
    const key = dateKey(day);
    const colEl = colRefs.current[key];
    if (!colEl) return;
    const r = colEl.getBoundingClientRect();
    const calc = (cy) => {
      let m = snap(dayStartMin + ((cy - r.top) / hourH) * 60, 30);
      return Math.max(dayStartMin, Math.min(m, rangeEnd * 60));
    };
    const anchor = calc(e.clientY);
    blockRef.current = { date: key, anchor, start: anchor, end: Math.min(anchor + 30, rangeEnd * 60), moved: false, r };
    setBlock({ ...blockRef.current });
    setDetails(null);
    setCreating(null);

    const onMove = (ev) => {
      const m = calc(ev.clientY);
      let start = Math.min(anchor, m), end = Math.max(anchor, m);
      if (end <= start) end = Math.min(start + 30, rangeEnd * 60);
      blockRef.current = { ...blockRef.current, start, end, moved: true };
      setBlock({ ...blockRef.current });
    };
    const onUp = () => {
      window.removeEventListener("mousemove", onMove);
      window.removeEventListener("mouseup", onUp);
      const b = blockRef.current;
      blockRef.current = null;
      setBlock(null);
      if (!b) return;
      let start = b.start, end = b.end;
      if (!b.moved) { end = Math.min(start + 60, rangeEnd * 60); if (end <= start) start = Math.max(dayStartMin, end - 60); }
      const top = b.r.top + ((start - dayStartMin) / 60) * hourH;
      const bottom = b.r.top + ((end - dayStartMin) / 60) * hourH;
      const rect = { left: b.r.left, right: b.r.right, top, bottom };
      setCreating({ draft: { date: b.date, start, dur: end - start, type: "computer", unit: "AIIT#1", who: "" }, rect });
    };
    window.addEventListener("mousemove", onMove);
    window.addEventListener("mouseup", onUp);
  };

  const saveEvent = (d) => {
    setEvents((evs) => {
      if (d.id) return evs.map((x) => x.id === d.id ? { ...d } : x);
      return [...evs, { ...d, id: Date.now() }];
    });
    setCreating(null);
  };
  const deleteEvent = (id) => {
    setEvents((evs) => evs.filter((x) => x.id !== id));
    setDetails(null);
    setGroup((g) => {
      if (!g) return null;
      const remaining = g.items.filter((x) => x.id !== id);
      return remaining.length > 1 ? { ...g, items: remaining } : null;
    });
  };

  const selectEvent = (ev, el) => {
    setCreating(null);
    setGroup(null);
    setDetails({ ev, rect: el.getBoundingClientRect() });
  };

  const openGroup = (item, el) => {
    setCreating(null);
    setDetails(null);
    setGroup({ items: item.items, start: item.start, dur: item.dur, rect: el.getBoundingClientRect() });
  };

  // ----- now line -----
  const nowMins = now.getHours() * 60 + now.getMinutes();
  const nowVisible = nowMins >= dayStartMin && nowMins <= rangeEnd * 60 &&
    days.some((d) => sameDay(d, now));
  const nowTop = ((nowMins - dayStartMin) / 60) * hourH;

  // hour labels
  const hours = [];
  for (let h = rangeStart; h <= rangeEnd; h++) hours.push(h);

  const rootStyle = {
    "--accent": accent,
    "--hour-h": `${hourH}px`,
    "--ndays": ndays,
    "--now": accent,
  };

  return (
    <div className="app" data-theme={dark ? "dark" : "light"} style={rootStyle}>
      {/* top bar */}
      <div className="topbar">
        <div className="brand"><span className="brand-mark">L</span>Lab Loan</div>
        <div className="nav-group">
          <button className="today-btn" onClick={goToday}>Today</button>
          <button className="icon-btn" onClick={() => go(-1)} aria-label="Previous">{Ico.chevL}</button>
          <button className="icon-btn" onClick={() => go(1)} aria-label="Next">{Ico.chevR}</button>
        </div>
        <div className="period">{periodLabel}<span className="year">{periodSub}</span></div>
        <div className="spacer" />
        <div className="seg">
          {[["day","Day"],["3day","3 Day"],["work","Work week"],["week","Week"]].map(([v, lbl]) => (
            <button key={v} className={view === v ? "active" : ""} onClick={() => setView(v)}>{lbl}</button>
          ))}
        </div>
        <button className="new-btn" onClick={() => {
          const d = days.find((x) => sameDay(x, now)) || days[0];
          setCreating({ draft: { date: dateKey(d), start: Math.max(dayStartMin, snap(nowMins,30)), dur: 60, type: "computer", unit: "AIIT#1", who: "" },
            rect: { left: window.innerWidth - 220, right: window.innerWidth - 220, top: 60, bottom: 60 } });
        }}>{Ico.plus}New booking</button>
      </div>

      {/* calendar */}
      <div className="cal-scroll">
        <div className="cal-head">
          <div className="corner">{h24 ? "24h" : "GMT"}</div>
          {days.map((d) => {
            const isToday = sameDay(d, now);
            const isWeekend = d.getDay() === 0 || d.getDay() === 6;
            return (
              <div key={dateKey(d)} className={`day-head${isToday ? " today" : ""}${isWeekend ? " weekend" : ""}`}>
                <span className="dh-name">{DAY_NAMES[d.getDay()]}</span>
                <span className="dh-num">{d.getDate()}</span>
              </div>
            );
          })}
        </div>

        <div className="cal-canvas" ref={canvasRef} style={{ height: canvasH }}>
          <div className="gutter">
            {hours.map((h) => (
              <div key={h} className="hour-label" style={{ top: (h - rangeStart) * hourH }}>
                {h === rangeStart ? "" : fmtTime(h * 60, h24)}
              </div>
            ))}
          </div>
          {days.map((day) => {
            const key = dateKey(day);
            const isWeekend = day.getDay() === 0 || day.getDay() === 6;
            const dayEvents = events.filter((e) => e.date === key);
            const layout = layoutDay(dayEvents);
            return (
              <div
                key={key}
                className={`day-col${isWeekend ? " weekend" : ""}`}
                ref={(el) => { colRefs.current[key] = el; }}
                onMouseDown={(e) => onBlockStart(e, day)}
              >
                {block && block.date === key && (
                  <div className="block-sel" style={{
                    top: ((block.start - dayStartMin) / 60) * hourH,
                    height: Math.max(((block.end - block.start) / 60) * hourH, 2),
                  }}>
                    <span className="block-label">{fmtTime(block.start, h24)} – {fmtTime(block.end, h24)}</span>
                  </div>
                )}
                {layout.map((it) => it.kind === "event" ? (
                  <EventCard key={it.ev.id} ev={it.ev} dark={dark} hourH={hourH} dayStartMin={dayStartMin}
                    h24={h24} left={it.left} width={it.width}
                    selected={details && details.ev.id === it.ev.id}
                    onSelect={selectEvent} />
                ) : (
                  <RollupCard key={`rollup-${it.start}-${it.left}`} item={it} dark={dark} hourH={hourH}
                    dayStartMin={dayStartMin} h24={h24} onOpen={openGroup} />
                ))}
              </div>
            );
          })}
          {nowVisible && (
            <div className="now-line" style={{ top: nowTop }}>
              <span className="now-flag">{fmtTime(nowMins, h24)}</span>
            </div>
          )}
        </div>
      </div>

      {creating && (
        <CreatePopover draft={creating.draft} anchorRect={creating.rect} h24={h24}
          onSave={saveEvent} onCancel={() => setCreating(null)} />
      )}
      {details && (
        <DetailsPopover ev={events.find((e) => e.id === details.ev.id) || details.ev} anchorRect={details.rect}
          dark={dark} h24={h24} onClose={() => setDetails(null)} onDelete={deleteEvent}
          onEdit={(ev) => { setDetails(null); setCreating({ draft: { ...ev }, rect: details.rect }); }} />
      )}
      {group && (
        <GroupPopover group={group} anchorRect={group.rect} dark={dark} h24={h24}
          onClose={() => setGroup(null)} onDelete={deleteEvent}
          onOpenOne={(ev, el) => { setGroup(null); setDetails({ ev, rect: el.getBoundingClientRect() }); }} />
      )}

      {/* Tweaks */}
      <TweaksPanel>
        <TweakSection label="Appearance" />
        <TweakColor label="Accent" value={accent}
          options={Object.values(ACCENTS)}
          onChange={(v) => setTweak("accent", Object.keys(ACCENTS).find((k) => ACCENTS[k] === v) || "Indigo")} />
        <TweakRadio label="Theme" value={t.theme} options={["Light", "Dark"]}
          onChange={(v) => setTweak("theme", v)} />
        <TweakRadio label="Density" value={t.density} options={["Compact", "Regular", "Comfortable"]}
          onChange={(v) => setTweak("density", v)} />
        <TweakSection label="Calendar" />
        <TweakSelect label="Time range" value={t.timeRange} options={Object.keys(RANGES)}
          onChange={(v) => setTweak("timeRange", v)} />
        <TweakRadio label="Time format" value={t.timeFormat} options={["12-hour", "24-hour"]}
          onChange={(v) => setTweak("timeFormat", v)} />
        <TweakRadio label="Week starts" value={t.weekStart} options={["Sunday", "Monday"]}
          onChange={(v) => setTweak("weekStart", v)} />
        <TweakToggle label="Show weekends (Week view)" value={t.showWeekends}
          onChange={(v) => setTweak("showWeekends", v)} />
      </TweaksPanel>
    </div>
  );
}

const TWEAK_DEFAULTS = /*EDITMODE-BEGIN*/{
  "accent": "Indigo",
  "theme": "Light",
  "density": "Regular",
  "timeRange": "Extended (6–10)",
  "timeFormat": "12-hour",
  "weekStart": "Monday",
  "showWeekends": true
}/*EDITMODE-END*/;

ReactDOM.createRoot(document.getElementById("root")).render(<App />);
