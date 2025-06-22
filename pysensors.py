import serial
import asyncio
import websockets

#update your port and baud rate
ser = serial.Serial('COM12', 9600)


async def send_serial_data(websocket):
    try:
        while True:
            #read a line from the serial port
            line = ser.readline().decode().strip()
            
            #send the ECG value directly (assuming it's just one number)
            await websocket.send(line)

            #add a slight delay to avoid flooding
            await asyncio.sleep(0.01)
    except websockets.exceptions.ConnectionClosed:
        print("Client disconnected")

async def main():
    async with websockets.serve(send_serial_data, "127.0.0.1", 8000):
        print("WebSocket server started at ws://127.0.0.1:8000")
        await asyncio.Future()  #run forever

if __name__ == "__main__":
    asyncio.run(main())
