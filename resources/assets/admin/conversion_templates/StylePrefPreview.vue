<template>
    <div class="ff_conv_media_preview">
        <div v-if="pref.layout == 'media_raw_html'" class="ffc_block_raw_html">
            <div class="ffc_raw_content" v-html="pref.raw_html" />
        </div>
        <div v-else :style="{ filter:  brightness }" class="fcc_block_media_attachment" :class="'fc_i_layout_' + pref.layout">
            <picture class="fc_image_holder">
                <img :style="{ 'object-position': imagePositionCSS, 'object-fit': mediaFit }" :src="pref.media" />
            </picture>
        </div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'StylePrefPreview',
    props: ['pref'],
    computed: {
        brightness() {
            const setValue = this.pref.brightness;
            if(!setValue) {
                return false;
            }
            let css = '';
            if(setValue > 0) {
                css += 'contrast('+((100 - setValue) / 100).toFixed(2)+') ';
            }
            return css + 'brightness('+ (1 + this.pref.brightness / 100) +')';
        },
        imagePositionCSS() {
            if(this.pref.layout == 'media_right_full' || this.pref.layout == 'media_left_full') {
                return this.pref.media_x_position+'%'+' '+ this.pref.media_y_position + '%';
            }
            return false;
        },
        mediaFit() {
            return this.pref.media_fit ?? 'cover';
        }
    }
}
</script>
