import React from 'react';
import PropTypes from 'prop-types';

const PelecardIframe = ({
    url,
    width = '100%',
    height = '600',
    frameBorder = '0',
    scrolling = 'auto',
    allowTransparency = 'true',
    title = 'Pelecard Payment',
    className = '',
    style = {},
    ...props
}) => {
    if (!url) {
        console.error('PelecardIframe: "url" prop is required.');
        return null;
    }

    const computedStyle = {
        width: typeof width === 'number' ? `${width}px` : width,
        height: typeof height === 'number' ? `${height}px` : height,
        border: 'none',
        ...style,
    };

    return (
        <iframe
            src={url}
            width={width}
            height={height}
            frameBorder={frameBorder}
            scrolling={scrolling}
            allowTransparency={allowTransparency}
            title={title}
            className={className}
            style={computedStyle}
            {...props}
        />
    );
};

PelecardIframe.propTypes = {
    url: PropTypes.string.isRequired,
    width: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    height: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    frameBorder: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    scrolling: PropTypes.string,
    allowTransparency: PropTypes.oneOfType([PropTypes.string, PropTypes.bool]),
    title: PropTypes.string,
    className: PropTypes.string,
    style: PropTypes.object,
};

export default PelecardIframe;
