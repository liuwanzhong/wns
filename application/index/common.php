<?php
function _close_layer($_info)
{
    echo "<script type='text/javascript'>
						alert('$_info');
						parent.location.reload()
						var index = parent.layer.getFrameIndex(window.name);
						parent.layer.close(index);
					</script>";
    exit();
    // window.location.reload()刷新当前页面.
    // parent.location.reload()刷新父亲对象（用于框架）
    // opener.location.reload()刷新父窗口对象（用于单开窗口）
    // top.location.reload()刷新最顶端对象（用于多开窗口）
}
function _alert_back($_info)
{
    echo "<script type='text/javascript'>
						alert('$_info');
						history.back();
					</script>";
    exit();
}
//弹窗返回自定义链接
function back_location($_info, $_url)
{
    echo "<script type='text/javascript'>
	    alert('$_info');
	    location.href='$_url'
	    </script>";
    exit();
}
