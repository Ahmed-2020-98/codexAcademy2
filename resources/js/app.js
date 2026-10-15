import './bootstrap';

/**
 * Dynamic watermark for the secure lesson player: shows the student's name,
 * phone, id and a live timestamp, drifts to a new spot every few seconds and
 * restores itself if it is removed or hidden through devtools.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('watermark', (cfg) => ({
        x: 8,
        y: 10,
        text: '',

        start() {
            const host = this.$el.querySelector('.cx-player');
            const mark = this.$refs.mark;
            if (!host || !mark) return;

            const stamp = () => new Date().toLocaleString('en-GB', { hour12: false });
            const move = () => {
                this.text = `${cfg.name} · ${cfg.phone ?? ''} · ${cfg.id} · ${stamp()}`;
                // Keep the whole label inside the player, whatever the name length or screen size.
                this.$nextTick(() => {
                    const maxX = Math.max(2, 98 - (mark.offsetWidth / host.clientWidth) * 100);
                    const maxY = Math.max(4, 96 - (mark.offsetHeight / host.clientHeight) * 100);
                    this.x = 2 + Math.random() * (maxX - 2);
                    this.y = 4 + Math.random() * (maxY - 4);
                });
            };

            move();
            setInterval(move, cfg.interval || 6000);

            // Only writes when tampering is detected, so it never loops on its own changes.
            new MutationObserver(() => {
                if (!host.contains(mark)) host.appendChild(mark);
                const cs = getComputedStyle(mark);
                if (cs.display === 'none' || cs.visibility === 'hidden' || parseFloat(cs.opacity) < 0.2) {
                    mark.style.setProperty('display', 'block', 'important');
                    mark.style.setProperty('visibility', 'visible', 'important');
                    mark.style.setProperty('opacity', '1', 'important');
                }
            }).observe(host, { childList: true, subtree: true, attributes: true, attributeFilter: ['style', 'class', 'hidden'] });
        },
    }));
});
