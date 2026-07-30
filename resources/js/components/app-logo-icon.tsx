import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(
    props: ImgHTMLAttributes<HTMLImageElement>,
) {
    return (
        <img src="/carenote-logo.webp" alt="" draggable={false} {...props} />
    );
}
