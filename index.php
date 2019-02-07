<!doctype html>
<html>
<header>
    <title>OCR</title>
    <meta http-equiv="Content-Type" content="text/html; charset=uft-8"/>
    <style>
      .grayscale{
    filter: grayscale(100%);
}
    </style>
</header>
<body>
    <div id="camera" style="width:320px; height:240px;"></div>
    <canvas id="canvas" width="320" height="240" style="position: absolute;top: 0;margin-top: 8px;"></canvas>
    <canvas id="snapshotCanvas" width="320" height="240"></canvas>
    <canvas  id="c3" width="320" height="240"></canvas>
    <canvas  id="c2" width="320" height="240"></canvas>
    <canvas  id="c" width="320" height="240"></canvas>
    
    <img src=""/>

    <p/>
        <div>
    <input type="range" id="start" name="volume" min="1" max="15" value="5">
    <label for="volume">Volume</label>
    <p/>
    <span id="value"></span>
    </div>
    <button name="take">Take</button>
    <input type="button" name="reset" value="Reset"/>

    <p>Result : <span id="result"></span></p> 

    <script src="js/webcam.js"></script>
    <script src="js/glfx.min.js"></script>
    <script src="js/tracking-min.js"></script>
    <!-- <script src="../node_modules/dat.gui/build/dat.gui.min.js"></script> -->
    <!-- <script src="js/color_camera_gui.js"></script> -->
    <script src="js/tesseract.min.js"></script>
    <script src="js/grafi.js"></script>
    
    <script>
        'use strict'

        document.querySelector('#value').textContent = document.querySelector('input[type=range]').value
		// $('#thTES').change(function(){
		// 	$('#thVAL').html(this.value);
		// });
        
        document.querySelector('input[type=range]').addEventListener('change', event => {
            document.querySelector('#value').textContent = event.target.value
        })
        var color = ['black','magenta', 'cyan', 'yellow']
        var canvas = document.getElementById('canvas')
        var context = canvas.getContext('2d')
        var snapshotCanvas = document.getElementById('snapshotCanvas')
        var snapshotContext = snapshotCanvas.getContext('2d')

        var count = {}
        var bool = false
        
        var path = window.location.protocol + '//' + window.location.host + '/SFC/assets/js/ocr'

        Webcam.set({
            width: 320,
            height: 240,
            image_format: 'png',
        })
        Webcam.attach('#camera')
        var video = document.querySelector('video')
        // document.querySelector('video').setAttribute('class', 'grayscale')

        // tracking.ColorTracker.registerColor('white', function(r, g, b) {
        //     if (r > 230 && g > 230 && b > 230) {
        //         return true;
        //     }
        //     return false;
        // });
        
        this.tesseract = Tesseract.create({
            workerPath: path + '/worker.js',
            langPath: path + '/lang/',
            corePath: path + '/index.js',
        })

        tracking.ColorTracker.registerColor('black', function(r, g, b) {
            if (r < 60 && g < 60 && b < 60) { //r < 60 && g < 60 && b < 60
                return true
            }
            return false
        });

        var tracker = new tracking.ColorTracker(color)
        // var img = document.querySelector('img')

        document.querySelector('input[name=reset]').addEventListener('click', event => {
            setColor(color)
            bool = false
        })

        document.querySelector('button[name=take]').addEventListener('click', event => {
            let original = undefined
            //grayscale
            original = snapshotContext.getImageData(0,0, snapshotCanvas.width, snapshotCanvas.height)
            snapshotContext.putImageData(grafi.grayscale(original), 0, 0)

            //blue component
            original = snapshotContext.getImageData(0,0, snapshotCanvas.width, snapshotCanvas.height)
            let data = original.data
            for (var i = 0; i < data.length; i+= 4) {
                data[i] =  0;
                data[i+1] = 0;
                data[i+2] = data[i+2] ^ 255;
            }
            snapshotContext.putImageData(original, 0, 0);

            //brightness
            original = snapshotContext.getImageData(0,0, snapshotCanvas.width, snapshotCanvas.height)
            snapshotContext.putImageData(grafi.brightness(original, {level: 0}), 0, 0)
            
            /*
            draw to new canvas algorithm, grayscale, blue component, brightness
            if draw straight algorithm not have affect
            */
            var fxCanvas = fx.canvas()
            let texture = fxCanvas.texture(snapshotCanvas)
            fxCanvas.draw(texture)
            // .hueSaturation(-1, -1)//grayscale
            // .unsharpMask(20, 2)
            // .brightnessContrast(0.4, 0.9)
            .update()

            let c3 = document.getElementById('c3')
            let c3Ctx = c3.getContext('2d');
            c3Ctx.clearRect(0, 0, c3.width, c3.height)
            c3Ctx.beginPath()
            c3Ctx.drawImage(fxCanvas, 0, 0)

            // clone to new canvas for threshold 
            let c2 = document.getElementById('c2')
            let c2Ctx = c2.getContext('2d');
            c2Ctx.clearRect(0, 0, c2.width, c2.height)
            c2Ctx.beginPath()
            c2Ctx.drawImage(fxCanvas, 0, 0)
            
            original = c2Ctx.getImageData(0,0, c2.width, c2.height)
            c2Ctx.putImageData(grafi.threshold(original, {level: document.querySelector('input[type=range]').value}), 0, 0)
        
            // create new canvas ready background and draw step before
            let output = document.getElementById('c')
            let outputCtx = output.getContext('2d');
            outputCtx.clearRect(0, 0, snapshotCanvas.width, snapshotCanvas.height)
            outputCtx.beginPath()
            outputCtx.fillStyle = 'rgb(213, 235, 194)'
            outputCtx.fillRect(0, 0, snapshotCanvas.width, snapshotCanvas.height)
            outputCtx.drawImage(c2, 0, 0)


            // ctx.font = '30px "Arial Black"'
            // ctx.fillText('1', 100, 40)
            // // ctx.fillText("囚犯離奇掙脫囚犯離奇掙脫", 100, 40)
            // ctx.font = '30px "Times New Roman"'
            // ctx.fillText('from beyond', 100, 80)
            // // ctx.fillText('2小時可換乘2次2小時可換乘2次', 100, 80)
            // ctx.font = '30px sans-serif'
            // ctx.fillText('the Cosmic Void', 100, 120)

            //final recognize
            tesseract.recognize(outputCtx, {
                // tessedit_char_blacklist:'TBoHnmNM WFOwf',
                tessedit_char_whitelist: '0123456789TBo'
                // progress: function(e){
                //     console.log(e)
                // }
            }).then(function(d){ 

                //A better way to "remove whitespace-only elements from an array".
                let symbols = d['symbols'].filter(function(o) {
                    return /\S/.test(o.text);
                });

                if ((symbols.length - 1) < 2) {
                    document.getElementById('result').innerHTML = symbols.map(o => o.text.trim() ).join('').trim()
                } 

                /*special label | 4 TB    |
                                |   ''''' | 
                                |   To    |*/            
                if ((symbols.length - 1) == 4 && /^\d+$/.test(Number(symbols[0].text)) && symbols[1].text == 'T' && symbols[2].text == 'B') {
                    document.getElementById('result').innerHTML = symbols[0].text
                }

                wait()
                document.querySelector('button[name=take]').click()

                async function wait () {
                    await sleep(200)
                }
             } )

        })

        tracking.track(video, tracker, { camera: true })
        tracker.on('track', trackCallback)

        document.querySelector('button[name=take]').click()

        function setColor (color) {
            tracker = new tracking.ColorTracker(color)
            tracking.track(video, tracker, { camera: true })
            tracker.on('track', trackCallback)
        }

        function trackCallback (event) {
            context.clearRect(0, 0, canvas.width, canvas.height)
            snapshotContext.clearRect(0, 0, snapshotCanvas.width, snapshotCanvas.height)
            snapshotContext.beginPath()
            // snapshotContext.fillRect(0, 0, snapshotCanvas.width, snapshotCanvas.height)

            if (event.data.length <= 0) return
            
            // let sumX = event.data.map(item => item.x).reduce((prev, next) => prev + next)
            // let sumY = event.data.map(item => item.y).reduce((prev, next) => prev + next)

            // var minX = event.data.reduce(function (prev, current) {
            //     return (prev.x < current.x) ? prev : current
            // })

            // let avgX = sumX / event.data.length
            // let avgY = sumY / event.data.length

            // let data = event.data.filter(function(o) {return o.x <= avgX})
            // data = data.filter(function(o) {return o.y <= avgY})

            let data = event.data.sort((a, b) => a.y < b.y ? -1 : 1) //order by y asc
            let minY = data.slice(0, 2)
            let min = minY.sort((a, b) => a.x < b.x ? -1 : 1) //order by x asc

            min.forEach(function(rect, indx) {

                // if (rect.color === 'custom') {
                //     rect.color = tracker.customColor
                // }

                // context.strokeStyle = '#f00f0f'//rect.color
                // context.strokeRect(rect.x, rect.y, rect.width, rect.height)
                // context.font = '11px Helvetica'
                // context.fillStyle = "#fff"
                // context.fillText('x: ' + rect.x + 'px', rect.x + rect.width + 5, rect.y + 11)
                // context.fillText('y: ' + rect.y + 'px', rect.x + rect.width + 5, rect.y + 22)

                if (indx == 0 ) {
                    context.strokeStyle = '#0000FF'
                    context.strokeRect(rect.x, rect.y, rect.width, rect.height)

                    // snapshotContext.drawImage(video, rect.x, rect.y, rect.width, rect.height, rect.x, rect.y, rect.width, rect.height)
                    Webcam.snap( (data_uri, canvas, context) => {
                        // snapshotContext.drawImage(canvas, rect.x + 10, rect.y + 10, rect.width -15, rect.height - 45, rect.x +10, rect.y+10, rect.width-15, rect.height-45)
                        snapshotContext.drawImage(canvas, rect.x+8, rect.y+8, rect.width-12, rect.height-30, rect.x, rect.y, rect.width, rect.height)
                        // image.src = snapshotCanvas.toDataURL()
                    })
                    // tracking.stop()

                    let num = count.hasOwnProperty(rect.color) ? count[rect.color] + 1 : 1
                    count[rect.color] = num
                    let max = Object.values(count).sort((prev, next) => next - prev)[0]
                    
                    if (max > 100 && bool == false) {
                        let key = Object.keys(count).filter(function(key) {return count[key] === max})[0]

                        setTimeout(() => setColor([key]), 1000)
                        bool = true
                        count = {}
                    }

                    //reset counter
                    if ((count.hasOwnProperty(rect.color)) && count[rect.color] > 100) count[rect.color] = 0
                }
            })
        }

        function sleep (ms) {
            return new Promise(resolve => setTimeout(resolve, ms))
        }

    </script>
</body>
</html>